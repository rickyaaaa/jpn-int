<?php

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\Question;
use App\Models\User;
use App\Services\OpenAiInterviewEvaluator;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InterviewBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_session_and_answer_upload_is_evaluated(): void
    {
        $this->seed(QuestionSeeder::class);
        Storage::fake('local');
        AccessCode::create(['code' => 'ABC123']);

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

        $this->post(route('candidate.login'), [
            'candidate_name' => 'Aulia Tester',
            'access_code' => 'ABC123',
        ])->assertRedirect(route('interview'));

        $this->assertDatabaseHas('access_codes', [
            'code' => 'ABC123',
            'is_used' => true,
            'used_by_name' => 'Aulia Tester',
        ]);

        $question = Question::query()->where('number', 1)->firstOrFail();

        $this->postJson(route('answers.store'), [
            'question_id' => $question->id,
            'duration_seconds' => 12,
            'audio_mime_type' => 'video/mp4',
            'audio' => UploadedFile::fake()->create('answer.webm', 24, 'audio/webm'),
        ])
            ->assertOk()
            ->assertJsonPath('answer.score', 86)
            ->assertJsonPath('answer.level', 'Baik')
            ->assertJsonPath('answered_count', 1)
            ->assertJsonPath('is_complete', false);

        $this->getJson(route('answers.index'))
            ->assertOk()
            ->assertJsonPath('answers.0.questionNumber', 1)
            ->assertJsonPath('answers.0.score', 86)
            ->assertJsonPath('answers.0.level', 'Baik')
            ->assertJsonPath('answered_count', 1);

        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'transcribed_text' => 'はい、日本語で答えました。',
            'score' => 86,
            'status' => 'completed',
        ]);
    }

    public function test_candidate_cannot_login_with_invalid_or_used_access_code(): void
    {
        AccessCode::create([
            'code' => 'USED01',
            'is_used' => true,
            'used_by_name' => 'Previous Candidate',
            'used_at' => now(),
        ]);

        $this->post(route('candidate.login'), [
            'candidate_name' => 'Aulia Tester',
            'access_code' => 'BAD123',
        ])
            ->assertSessionHasErrors('access_code')
            ->assertRedirect();

        $this->post(route('candidate.login'), [
            'candidate_name' => 'Aulia Tester',
            'access_code' => 'USED01',
        ])
            ->assertSessionHasErrors('access_code')
            ->assertRedirect();
    }

    public function test_admin_generate_token_creates_unused_access_code(): void
    {
        $admin = User::create([
            'name' => 'Admin Ricksite',
            'username' => 'admin',
            'email' => 'admin@ricksite.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.generate-token'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $accessCode = AccessCode::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $accessCode->code);
        $this->assertFalse($accessCode->is_used);
    }

    public function test_admin_can_login_and_view_dashboard(): void
    {
        User::create([
            'name' => 'Admin Ricksite',
            'username' => 'admin',
            'email' => 'admin@ricksite.com',
            'password' => Hash::make('password'),
        ]);
        AccessCode::create(['code' => 'VIEW01']);

        $this->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('VIEW01')
            ->assertSee('Generate New Access Code');
    }

    public function test_admin_can_update_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Ricksite',
            'username' => 'admin',
            'email' => 'admin@ricksite.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.password.update'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword', $admin->fresh()->password));

        $this->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'newpassword',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
