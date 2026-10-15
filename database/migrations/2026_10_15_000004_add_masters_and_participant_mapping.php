<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->unique(['office_id', 'name']);
        });
        Schema::create('speakers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('organization')->nullable();
            $t->string('type', 20)->default('external');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->unique(['office_id', 'name']);
            $t->timestamps();
        });
        Schema::table('learning_sessions', fn (Blueprint $t) => $t->foreignId('speaker_id')->nullable()->constrained()->restrictOnDelete());
        Schema::table('enrollments', function (Blueprint $t) {
            $t->foreignId('mapped_institution_id')->nullable()->constrained('institutions')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->index(['activity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('mapped_institution_id');
            $t->dropColumn('revision');
            $t->dropIndex(['activity_id', 'status']);
        });
        Schema::table('learning_sessions', fn (Blueprint $t) => $t->dropConstrainedForeignId('speaker_id'));
        Schema::dropIfExists('speakers');
        Schema::table('institutions', function (Blueprint $t) {
            $t->dropUnique(['office_id', 'name']);
            $t->dropColumn(['is_active', 'revision']);
        });
    }
};
