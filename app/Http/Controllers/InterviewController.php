<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\TestSession;
use App\Services\OpenAiInterviewEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class InterviewController extends Controller
{
    public function start(): View
    {
        return view('pages.start');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($credentials['username'] !== 'admin' || $credentials['password'] !== 'password') {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Username atau password tidak sesuai.']);
        }

        $candidate = Candidate::create([
            'username' => $credentials['username'],
            'name' => 'Admin Tester',
        ]);

        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);

        $request->session()->put('candidate_id', $candidate->id);
        $request->session()->put('test_session_id', $session->id);
        $request->session()->regenerate();

        return redirect()->route('interview');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['candidate_id', 'test_session_id']);
        $request->session()->regenerateToken();

        return redirect()->route('start');
    }

    public function interview(Request $request): View|RedirectResponse
    {
        $session = $this->currentSession($request);

        if (! $session) {
            return redirect()->route('start');
        }

        $answers = $session->answers()
            ->where('status', 'completed')
            ->with('question')
            ->get()
            ->keyBy('question_id');

        $questions = Question::query()
            ->orderBy('number')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'number' => $question->number,
                'text' => $question->japanese_text,
                'answered' => $answers->has($question->id),
            ])
            ->values();

        if ($answers->count() >= 10) {
            return redirect()->route('results');
        }

        return view('pages.interview', [
            'questions' => $questions,
            'answeredCount' => $answers->count(),
        ]);
    }

    public function storeAnswer(
        Request $request,
        OpenAiInterviewEvaluator $evaluator
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

        if ($existingAnswer && $existingAnswer->status === 'completed') {
            return response()->json(['message' => 'Pertanyaan ini sudah dijawab.'], 422);
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

        try {
            $absolutePath = Storage::disk('local')->path($audioPath);
            $transcript = $evaluator->transcribe($absolutePath, $mimeType);
            $evaluation = $evaluator->evaluate($question, $transcript, $answer->duration_seconds);

            DB::transaction(function () use ($answer, $evaluation, $session, $transcript): void {
                $answer->update([
                    'transcribed_text' => $transcript,
                    'score' => $evaluation['overall_score'],
                    'pronunciation_score' => $evaluation['pronunciation_score'],
                    'fluency_score' => $evaluation['fluency_score'],
                    'grammar_score' => $evaluation['grammar_score'],
                    'feedback' => $evaluation['feedback'],
                    'status' => 'completed',
                    'error_message' => null,
                ]);

                $this->refreshSessionScore($session->fresh());
            });

            $session = $session->fresh('answers');

            return response()->json([
                'answer' => $this->answerPayload($answer->fresh('question')),
                'answered_count' => $session->answers()->where('status', 'completed')->count(),
                'is_complete' => $session->status === 'completed',
                'results_url' => route('results'),
            ]);
        } catch (Throwable $error) {
            $answer->update([
                'status' => 'failed',
                'error_message' => $error->getMessage(),
            ]);

            report($error);

            return response()->json([
                'message' => $this->publicErrorMessage($error->getMessage()),
            ], 422);
        }
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

    protected function refreshSessionScore(TestSession $session): void
    {
        $completedAnswers = $session->answers()
            ->where('status', 'completed')
            ->get();

        $updates = [
            'total_score' => $completedAnswers->avg('score'),
        ];

        if ($completedAnswers->count() >= 10) {
            $updates['status'] = 'completed';
            $updates['end_time'] = now();
        }

        $session->update($updates);
    }

    protected function answerPayload(Answer $answer): array
    {
        return [
            'questionNumber' => $answer->question->number,
            'question' => $answer->question->japanese_text,
            'duration' => $this->formatDuration($answer->duration_seconds),
            'score' => $answer->score,
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

    protected function publicErrorMessage(string $message): string
    {
        if (str_contains($message, 'empty transcript')) {
            return 'OpenAI tidak mendeteksi suara yang bisa ditranskrip. Pastikan mikrofon benar, rekam minimal 5 detik, dan bicara cukup jelas.';
        }

        return $message;
    }
}
