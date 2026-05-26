<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('username')->index();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->float('total_score')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->text('japanese_text');
            $table->text('indonesian_translation')->nullable();
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('audio_path');
            $table->text('transcribed_text')->nullable();
            $table->float('score')->nullable();
            $table->float('pronunciation_score')->nullable();
            $table->float('fluency_score')->nullable();
            $table->float('grammar_score')->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['test_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('test_sessions');
        Schema::dropIfExists('candidates');
    }
};
