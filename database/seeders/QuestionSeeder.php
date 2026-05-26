<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            1 => 'タバコを吸いますか。お酒を飲みますか。',
            2 => '短所と長所を教えてください。',
            3 => '日本で仕事している家族か親戚はいますか。',
            4 => 'どれぐらい日本語を勉強しましたか。',
            5 => '共同生活は大丈夫ですか。',
            6 => '断食はやっていますか。',
            7 => 'お祈りの時間を調整できますか。',
            8 => '日本の仕事の中で職種がいっぱいありますが、なんで我々の会社で仕事したいですか。',
            9 => '日本へ行く目的は、割合にすると、何割仕事か、何割遊びか、正直に答えてください。',
            10 => '日本の文化で何を知っていますか。',
        ];

        foreach ($questions as $number => $text) {
            Question::updateOrCreate(
                ['number' => $number],
                ['japanese_text' => $text]
            );
        }
    }
}
