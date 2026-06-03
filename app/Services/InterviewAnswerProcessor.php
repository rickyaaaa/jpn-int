<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\TestSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InterviewAnswerProcessor
{
    public function __construct(
        protected OpenAiInterviewEvaluator $evaluator
    ) {}

    public function process(int $answerId, string $mimeType): void
    {
        $answer = Answer::query()
            ->with(['question', 'testSession'])
            ->find($answerId);

        if (! $answer || $answer->status === 'completed') {
            return;
        }

        try {
            $absolutePath = Storage::disk('local')->path($answer->audio_path);
            $transcript = $this->evaluator->transcribe($absolutePath, $mimeType);
            $evaluation = $this->evaluator->evaluate($answer->question, $transcript, $answer->duration_seconds);

            DB::transaction(function () use ($answer, $evaluation, $transcript): void {
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

                $this->refreshSessionScore($answer->testSession->fresh());
            });
        } catch (Throwable $error) {
            $answer->update([
                'status' => 'failed',
                'error_message' => $this->publicErrorMessage($error->getMessage()),
            ]);

            report($error);
        }
    }

    protected function refreshSessionScore(TestSession $session): void
    {
        $completedAnswers = $session->answers()
            ->where('status', 'completed')
            ->get();

        $updates = [
            'total_score' => $completedAnswers->avg('score'),
        ];

        $questionCount = \App\Models\Question::query()->count();

        if ($completedAnswers->count() >= $questionCount) {
            $updates['status'] = 'completed';
            $updates['end_time'] = $session->end_time ?? now();
        }

        $session->update($updates);
    }

    protected function publicErrorMessage(string $message): string
    {
        if (str_contains($message, 'empty transcript')) {
            return 'OpenAI tidak mendeteksi suara yang bisa ditranskrip. Pastikan mikrofon benar, rekam minimal 5 detik, dan bicara cukup jelas.';
        }

        return $message;
    }
}
