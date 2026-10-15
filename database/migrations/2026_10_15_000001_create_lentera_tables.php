<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('institutions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('participant')->index();
            $t->foreignId('institution_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(true);
            $t->string('identity_number')->nullable();
            $t->string('category')->nullable();
            $t->string('department')->nullable();
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->boolean('is_demo')->default(false);
            $t->timestamps();
        });
        Schema::create('l1_instruments', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->unsignedInteger('version');
            $t->json('questions');
            $t->timestamp('published_at')->nullable();
            $t->unique(['code', 'version']);
            $t->timestamps();
        });
        Schema::create('learning_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->restrictOnDelete();
            $t->foreignId('l1_instrument_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->string('speaker_name');
            $t->timestamp('starts_at');
            $t->timestamp('l1_opens_at');
            $t->timestamp('l1_closes_at');
            $t->timestamps();
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('activity_id')->constrained()->restrictOnDelete();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->string('institution_name');
            $t->string('participant_category')->nullable();
            $t->string('status')->default('active');
            $t->unique(['user_id', 'activity_id']);
            $t->index(['institution_id', 'activity_id']);
            $t->timestamps();
        });
        Schema::create('l1_responses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('learning_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('l1_instrument_id')->constrained()->restrictOnDelete();
            $t->json('instrument_snapshot');
            $t->string('speaker_name');
            $t->json('answers');
            $t->unsignedTinyInteger('score_sum');
            $t->text('feedback')->nullable();
            $t->timestamp('submitted_at');
            $t->unique(['enrollment_id', 'learning_session_id']);
        });
    }

    public function down(): void
    {
        foreach (['l1_responses', 'enrollments', 'learning_sessions', 'l1_instruments', 'activities'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('institution_id');
            $t->dropConstrainedForeignId('office_id');
            $t->dropColumn(['role', 'is_active', 'identity_number', 'category', 'department']);
        });
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('offices');
    }
};
