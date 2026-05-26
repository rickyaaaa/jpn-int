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
            'audio' => [
                'required',
                'file',
                'max:20480',
                'mimetypes:audio/webm,video/webm,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/ogg,application/octet-stream',
            ],
        ]);

        $question = Question::findOrFail($validated['question_id']);

        $existingAnswer = $session->answers()->where('question_id', $question->id)->first();

        if ($existingAnswer && $existingAnswer->status === 'completed') {
            return response()->json(['message' => 'Pertanyaan ini sudah dijawab.'], 422);
        }

        $existingAnswer?->delete();

        $audioPath = $request->file('audio')->store("answers/session-{$session->id}", 'local');
        $answer = Answer::create([
            'test_session_id' => $session->id,
            'question_id' => $question->id,
            'audio_path' => $audioPath,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'status' => 'processing',
        ]);

        try {
            $absolutePath = Storage::disk('local')->path($audioPath);
            $mimeType = $request->file('audio')->getMimeType() ?: 'audio/webm';
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
                'message' => $error->getMessage(),
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
}
