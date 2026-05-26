<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'question_id',
        'audio_path',
        'transcribed_text',
        'score',
        'pronunciation_score',
        'fluency_score',
        'grammar_score',
        'feedback',
        'duration_seconds',
        'status',
        'error_message',
    ];

    protected $casts = [
        'score' => 'float',
        'pronunciation_score' => 'float',
        'fluency_score' => 'float',
        'grammar_score' => 'float',
        'duration_seconds' => 'integer',
    ];

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
