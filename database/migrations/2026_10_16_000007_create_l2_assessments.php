<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('l2_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name', 160);
            $table->unsignedInteger('version');
            $table->text('instructions')->nullable();
            $table->json('questions');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->string('creation_key', 64)->unique();
            $table->string('creation_hash', 64);
            $table->unique(['code', 'version']);
            $table->timestamps();
        });
        Schema::create('l2_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_session_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('l2_instrument_id')->constrained()->restrictOnDelete();
            $table->timestamp('pre_opens_at');
            $table->timestamp('pre_closes_at');
            $table->timestamp('post_opens_at');
            $table->timestamp('post_closes_at');
            $table->boolean('enabled')->default(false);
            $table->boolean('require_pretest')->default(false);
            $table->boolean('results_visible')->default(false);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('l2_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('l2_assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('l2_instrument_id')->constrained()->restrictOnDelete();
            $table->string('stage', 4);
            $table->json('instrument_snapshot');
            $table->json('answers');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('correct_count')->nullable();
            $table->unsignedInteger('question_count');
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->unique(['l2_assessment_id', 'enrollment_id', 'stage'], 'l2_attempt_identity_unique');
            $table->index(['l2_assessment_id', 'stage', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('l2_attempts');
        Schema::dropIfExists('l2_assessments');
        Schema::dropIfExists('l2_instruments');
    }
};
