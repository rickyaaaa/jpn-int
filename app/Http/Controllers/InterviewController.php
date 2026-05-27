<?php

namespace App\Http\Controllers;

use App\Models\AccessCode;
use App\Models\Answer;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\TestSession;
use App\Services\InterviewAnswerProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InterviewController extends Controller
{
    public function start(): View
    {
        return view('pages.start');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_name' => ['required', 'string', 'max:120'],
            'access_code' => ['required', 'string', 'size:6'],
        ]);

        $candidateName = trim($validated['candidate_name']);
        $accessCodeValue = Str::upper(trim($validated['access_code']));

        $accessCode = AccessCode::query()
            ->where('code', $accessCodeValue)
            ->where('is_used', false)
            ->first();

        if (! $accessCode) {
            return back()
                ->withInput($request->only('candidate_name'))
                ->withErrors(['access_code' => 'Kode akses tidak valid atau sudah pernah digunakan.']);
        }

        [$candidate, $session] = DB::transaction(function () use ($accessCode, $accessCodeValue, $candidateName): array {
            $accessCode = AccessCode::query()
                ->whereKey($accessCode->id)
                ->where('code', $accessCodeValue)
                ->where('is_used', false)
                ->lockForUpdate()
                ->first();

            if (! $accessCode) {
                return [null, null];
            }

            $accessCode->update([
                'is_used' => true,
                'used_by_name' => $candidateName,
                'used_at' => now(),
            ]);

            $candidate = Candidate::create([
                'username' => $accessCodeValue,
                'name' => $candidateName,
            ]);

            $session = TestSession::create([
                'candidate_id' => $candidate->id,
                'start_time' => now(),
                'status' => 'in_progress',
            ]);

            return [$candidate, $session];
        });

        if (! $candidate || ! $session) {
            return back()
                ->withInput($request->only('candidate_name'))
                ->withErrors(['access_code' => 'Kode akses baru saja digunakan. Minta kode baru ke admin.']);
        }

        $request->session()->put('candidate_id', $candidate->id);
        $request->session()->put('candidate_name', $candidate->name);
        $request->session()->put('test_session_id', $session->id);
        $request->session()->regenerate();

        return redirect()->route('interview');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['candidate_id', 'candidate_name', 'test_session_id']);
        $request->session()->regenerateToken();

        return redirect()->route('start');
    }

    public function generateToken(): JsonResponse
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (AccessCode::query()->where('code', $code)->exists());

        $accessCode = AccessCode::create([
            'code' => $code,
            'is_used' => false,
        ]);

        return response()->json([
            'code' => $accessCode->code,
            'is_used' => $accessCode->is_used,
            'created_at' => $accessCode->created_at?->toISOString(),
        ]);
    }

    public function interview(Request $request): View|RedirectResponse
    {
        $session = $this->currentSession($request);

        if (! $session) {
            return redirect()->route('start');
        }

        $answers = $this->submittedAnswers($session)
            ->with('question')
            ->get()
            ->sortBy(fn (Answer $answer) => $answer->question->number)
            ->values();

        $answeredQuestionIds = $answers->pluck('question_id')->all();

        $questions = Question::query()
            ->orderBy('number')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'number' => $question->number,
                'text' => $question->japanese_text,
                'answered' => in_array($question->id, $answeredQuestionIds, true),
            ])
            ->values();

        if ($answers->count() >= 10) {
            return redirect()->route('results');
        }

        return view('pages.interview', [
            'questions' => $questions,
            'answeredCount' => $answers->count(),
            'answers' => $answers
                ->map(fn (Answer $answer) => $this->answerPayload($answer))
                ->values(),
        ]);
    }

    public function answers(Request $request): JsonResponse
    {
        $session = $this->currentSession($request);

        if (! $session) {
            return response()->json(['message' => 'Sesi login tidak ditemukan. Silakan login ulang.'], 401);
        }

        $answers = $session->answers()
            ->with('question')
            ->orderBy('question_id')
            ->get()
            ->sortBy(fn (Answer $answer) => $answer->question->number)
            ->map(fn (Answer $answer) => $this->answerPayload($answer))
            ->values();

        return response()->json([
            'answers' => $answers,
            'answered_count' => $this->submittedAnswerCount($session),
            'is_complete' => $session->fresh()->status === 'completed',
        ]);
    }

    public function storeAnswer(
        Request $request,
        InterviewAnswerProcessor $processor
    ): JsonResponse {
        $session = $this->currentSession($request);

        if (! $session) {
            return response()->json(['message' => 'Sesi login tidak ditemukan. Silakan login ulang.'], 401);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'audio_mime_type' => ['nullable', 'string', 'max:120'],
            'audio' => ['required', 'file', 'max:20480'],
        ]);

        $uploadedAudio = $request->file('audio');
        $mimeType = $this->resolveAudioMimeType($request);

        if (! $this->isSupportedAudioMimeType($mimeType)) {
            return response()->json([
                'message' => 'Format audio dari browser ini belum didukung. Coba buka langsung lewat Chrome/Safari terbaru, bukan dari in-app browser WhatsApp/Instagram.',
                'detected_mime_type' => $mimeType,
            ], 422);
        }

        if ($uploadedAudio->getSize() !== null && $uploadedAudio->getSize() < 1024) {
            return response()->json([
                'message' => 'File rekaman kosong atau terlalu kecil. Coba rekam ulang dengan suara yang lebih jelas.',
            ], 422);
        }

        $question = Question::findOrFail($validated['question_id']);

        $existingAnswer = $session->answers()->where('question_id', $question->id)->first();

        if ($existingAnswer && in_array($existingAnswer->status, ['processing', 'completed'], true)) {
            $submittedCount = $this->submittedAnswerCount($session);

            return response()->json([
                'answer' => $this->answerPayload($existingAnswer->fresh('question')),
                'answered_count' => $submittedCount,
                'is_complete' => $submittedCount >= Question::query()->count(),
                'results_url' => route('results'),
            ]);
        }

        $existingAnswer?->delete();

        $extension = $this->extensionForMimeType($mimeType);
        $audioPath = $uploadedAudio->storeAs(
            "answers/session-{$session->id}",
            'answer-'.$question->number.'-'.Str::uuid().'.'.$extension,
            'local'
        );
        $answer = Answer::create([
            'test_session_id' => $session->id,
            'question_id' => $question->id,
            'audio_path' => $audioPath,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'status' => 'processing',
        ]);

        if (app()->runningUnitTests()) {
            $processor->process($answer->id, $mimeType);
        } else {
            app()->terminating(fn () => $processor->process($answer->id, $mimeType));
        }

        $submittedCount = $this->submittedAnswerCount($session);

        return response()->json([
            'answer' => $this->answerPayload($answer->fresh('question')),
            'answered_count' => $submittedCount,
            'is_complete' => $submittedCount >= Question::query()->count(),
            'results_url' => route('results'),
        ]);
    }

    public function results(Request $request): View|RedirectResponse
    {
        $session = $this->currentSession($request);

        if (! $session) {
            return redirect()->route('start');
        }

        $session->load(['candidate', 'answers.question']);

        return view('pages.results', [
            'session' => $session,
            'answers' => $session->answers
                ->sortBy(fn (Answer $answer) => $answer->question->number)
                ->map(fn (Answer $answer) => $this->answerPayload($answer))
                ->values(),
        ]);
    }

    protected function currentSession(Request $request): ?TestSession
    {
        $sessionId = $request->session()->get('test_session_id');

        if (! $sessionId) {
            return null;
        }

        return TestSession::query()
            ->with('answers')
            ->find($sessionId);
    }

    protected function submittedAnswers(TestSession $session)
    {
        return $session->answers()
            ->whereIn('status', ['processing', 'completed']);
    }

    protected function submittedAnswerCount(TestSession $session): int
    {
        return $this->submittedAnswers($session)->count();
    }

    protected function answerPayload(Answer $answer): array
    {
        return [
            'questionNumber' => $answer->question->number,
            'question' => $answer->question->japanese_text,
            'duration' => $this->formatDuration($answer->duration_seconds),
            'score' => $answer->score,
            'level' => $this->levelForScore($answer),
            'pronunciationScore' => $answer->pronunciation_score,
            'fluencyScore' => $answer->fluency_score,
            'grammarScore' => $answer->grammar_score,
            'transcript' => $answer->transcribed_text,
            'feedback' => $answer->feedback,
            'status' => $answer->status,
            'errorMessage' => $answer->error_message,
            'submittedAt' => $answer->created_at?->toISOString(),
        ];
    }

    protected function formatDuration(?int $seconds): string
    {
        $seconds ??= 0;

        return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
    }

    protected function levelForScore(Answer $answer): string
    {
        if ($answer->status === 'processing') {
            return 'Sedang diproses';
        }

        if ($answer->status === 'failed') {
            return 'Perlu rekam ulang';
        }

        $score = (int) round($answer->score ?? 0);

        return match (true) {
            $score >= 90 => 'Sangat baik',
            $score >= 80 => 'Baik',
            $score >= 70 => 'Cukup',
            default => 'Perlu latihan',
        };
    }

    protected function resolveAudioMimeType(Request $request): string
    {
        $candidates = [
            $request->input('audio_mime_type'),
            $request->file('audio')?->getClientMimeType(),
            $request->file('audio')?->getMimeType(),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtolower(strtok(trim($candidate), ';'));
            }
        }

        return 'application/octet-stream';
    }

    protected function isSupportedAudioMimeType(string $mimeType): bool
    {
        return in_array($mimeType, [
            'audio/webm',
            'video/webm',
            'audio/wav',
            'audio/x-wav',
            'audio/wave',
            'audio/mpeg',
            'audio/mp3',
            'audio/mp4',
            'video/mp4',
            'audio/ogg',
            'video/ogg',
            'audio/aac',
            'audio/x-aac',
            'audio/m4a',
            'audio/x-m4a',
            'video/quicktime',
            'application/octet-stream',
        ], true);
    }

    protected function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'audio/mp4', 'video/mp4' => 'mp4',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/ogg', 'video/ogg' => 'ogg',
            'audio/wav', 'audio/x-wav', 'audio/wave' => 'wav',
            'audio/aac', 'audio/x-aac' => 'aac',
            'audio/m4a', 'audio/x-m4a' => 'm4a',
            'video/quicktime' => 'mov',
            default => 'webm',
        };
    }
}
