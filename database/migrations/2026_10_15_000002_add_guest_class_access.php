<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_sessions', function (Blueprint $t) {
            $t->string('join_token', 64)->nullable()->unique();
            $t->boolean('join_enabled')->default(false);
            $t->timestamp('join_opens_at')->nullable();
            $t->timestamp('join_closes_at')->nullable();
        });
        Schema::table('enrollments', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id')->nullable()->change();
            $t->unsignedBigInteger('institution_id')->nullable()->change();
            $t->foreignId('joined_session_id')->nullable()->constrained('learning_sessions')->restrictOnDelete();
            $t->string('guest_access_hash', 64)->nullable()->unique();
            $t->string('full_name')->nullable();
            $t->string('identity_number')->nullable();
            $t->string('department')->nullable();
            $t->string('faculty')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
        });
    }

    public function down(): void
    {
        // Existing guest records must be retained: user/institution remain nullable.
        Schema::table('enrollments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('joined_session_id');
            $t->dropUnique(['guest_access_hash']);
            $t->dropColumn(['guest_access_hash', 'full_name', 'identity_number', 'department', 'faculty', 'phone', 'email']);
        });
        Schema::table('learning_sessions', function (Blueprint $t) {
            $t->dropUnique(['join_token']);
            $t->dropColumn(['join_token', 'join_enabled', 'join_opens_at', 'join_closes_at']);
        });
    }
};
