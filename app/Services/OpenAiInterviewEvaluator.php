<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiInterviewEvaluator
{
    public function transcribe(string $absoluteAudioPath, string $mimeType): string
    {
        $response = $this->client()
            ->attach(
                'file',
                fopen($absoluteAudioPath, 'r'),
                basename($absoluteAudioPath),
                ['Content-Type' => $mimeType]
            )
            ->post($this->url('/audio/transcriptions'), [
                'model' => config('services.openai.transcription_model'),
                'language' => 'ja',
                'response_format' => 'json',
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'OpenAI transcription request failed.'));
        }

        $text = trim((string) data_get($response->json(), 'text'));

        if ($text === '') {
            throw new RuntimeException('OpenAI transcription returned an empty transcript.');
        }

        return $text;
    }

    /**
     * @return array{overall_score:int, pronunciation_score:int, fluency_score:int, grammar_score:int, feedback:string}
     */
    public function evaluate(Question $question, string $transcript, ?int $durationSeconds): array
    {
        $response = $this->client()
            ->post($this->url('/responses'), [
                'model' => config('services.openai.evaluation_model'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            "Pertanyaan Jepang:\n%s\n\nTranskrip kandidat:\n%s\n\nDurasi rekaman: %s detik.",
                            $question->japanese_text,
                            $transcript,
                            $durationSeconds ?? 'tidak diketahui'
                        ),
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'japanese_interview_evaluation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'overall_score' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                ],
                                'pronunciation_score' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                ],
                                'fluency_score' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                ],
                                'grammar_score' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                ],
                                'feedback' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'overall_score',
                                'pronunciation_score',
                                'fluency_score',
                                'grammar_score',
                                'feedback',
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'OpenAI evaluation request failed.'));
        }

        $payload = json_decode($this->responseText($response->json()), true);

        if (! is_array($payload)) {
            throw new RuntimeException('OpenAI evaluation returned invalid JSON.');
        }

        return [
            'overall_score' => $this->clampScore($payload['overall_score'] ?? 0),
            'pronunciation_score' => $this->clampScore($payload['pronunciation_score'] ?? 0),
            'fluency_score' => $this->clampScore($payload['fluency_score'] ?? 0),
            'grammar_score' => $this->clampScore($payload['grammar_score'] ?? 0),
            'feedback' => trim((string) ($payload['feedback'] ?? 'Tidak ada feedback.')),
        ];
    }

    protected function client(): PendingRequest
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY belum diatur di file .env.');
        }

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(config('services.openai.timeout'));
    }

    protected function url(string $path): string
    {
        return rtrim(config('services.openai.base_url'), '/').$path;
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah evaluator wawancara kerja Bahasa Jepang untuk kandidat Indonesia.
Nilai jawaban berdasarkan transkrip hasil speech-to-text dan konteks pertanyaan.
Berikan skor 0-100 untuk keseluruhan, pronunciation, fluency, dan grammar.
Karena input utama adalah transkrip, pronunciation_score harus diperkirakan secara hati-hati dari kejelasan transkrip, panjang jawaban, dan kelancaran, bukan diklaim sebagai analisis fonetik penuh.
Feedback wajib ringkas, dalam Bahasa Indonesia, dan berisi 1-2 saran praktis.
PROMPT;
    }

    protected function responseText(array $payload): string
    {
        $direct = data_get($payload, 'output_text');

        if (is_string($direct) && trim($direct) !== '') {
            return $direct;
        }

        foreach ((array) data_get($payload, 'output', []) as $output) {
            foreach ((array) data_get($output, 'content', []) as $content) {
                $text = data_get($content, 'text');

                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            }
        }

        throw new RuntimeException('OpenAI evaluation response did not include text output.');
    }

    protected function errorMessage(?array $payload, string $fallback): string
    {
        return (string) data_get($payload, 'error.message', $fallback);
    }

    protected function clampScore(mixed $score): int
    {
        return max(0, min(100, (int) round((float) $score)));
    }
}
