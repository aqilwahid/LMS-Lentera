<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_aliases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->string('alias');
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->timestamps();
            $t->unique(['office_id', 'alias']);
        });
        Schema::create('import_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->foreignId('learning_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('file_name');
            $t->string('source_hash', 64);
            $t->timestamps();
            $t->unique(['learning_session_id', 'source_hash']);
        });
        Schema::create('import_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('line');
            $t->string('status', 12);
            $t->string('row_key', 64)->nullable();
            $t->json('data');
            $t->string('error')->nullable();
            $t->index(['import_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('institution_aliases');
    }
};
