<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Services\OpenAiInterviewEvaluator;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InterviewBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_session_and_answer_upload_is_evaluated(): void
    {
        $this->seed(QuestionSeeder::class);
        Storage::fake('local');

        $this->app->instance(OpenAiInterviewEvaluator::class, new class extends OpenAiInterviewEvaluator
        {
            public function transcribe(string $absoluteAudioPath, string $mimeType): string
            {
                return 'はい、日本語で答えました。';
            }

            public function evaluate(Question $question, string $transcript, ?int $durationSeconds): array
            {
                return [
                    'overall_score' => 86,
                    'pronunciation_score' => 82,
                    'fluency_score' => 88,
                    'grammar_score' => 84,
                    'feedback' => 'Jawaban cukup lancar dan struktur kalimat dasar sudah jelas.',
                ];
            }
        });

        $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('interview'));

        $question = Question::query()->where('number', 1)->firstOrFail();

        $this->postJson(route('answers.store'), [
            'question_id' => $question->id,
            'duration_seconds' => 12,
            'audio_mime_type' => 'video/mp4',
            'audio' => UploadedFile::fake()->create('answer.webm', 24, 'audio/webm'),
        ])
            ->assertOk()
            ->assertJsonPath('answer.score', 86)
            ->assertJsonPath('answered_count', 1)
            ->assertJsonPath('is_complete', false);

        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'transcribed_text' => 'はい、日本語で答えました。',
            'score' => 86,
            'status' => 'completed',
        ]);
    }
}
