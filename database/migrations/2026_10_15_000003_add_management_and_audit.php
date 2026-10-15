<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            $t->unsignedInteger('revision')->default(1);
        });
        Schema::table('learning_sessions', function (Blueprint $t) {
            $t->unsignedInteger('revision')->default(1);
        });
        Schema::create('audit_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->string('entity_type', 40);
            $t->unsignedBigInteger('entity_id');
            $t->string('action', 50);
            $t->json('changes');
            $t->timestamp('created_at');
            $t->index(['office_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_entries');
        Schema::table('learning_sessions', fn (Blueprint $t) => $t->dropColumn('revision'));
        Schema::table('activities', fn (Blueprint $t) => $t->dropColumn('revision'));
    }
};
