<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('learning_session_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->boolean('is_visible')->default(false);
            $t->unsignedBigInteger('current_version_id')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('material_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version_number');
            $t->string('storage_path');
            $t->string('original_filename');
            $t->unsignedBigInteger('size_bytes');
            $t->string('sha256', 64);
            $t->string('upload_key', 64)->unique();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at');
            $t->unique(['material_id', 'version_number']);
        });
        Schema::table('materials', fn (Blueprint $t) => $t->foreign('current_version_id')->references('id')->on('material_versions')->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('materials', fn (Blueprint $t) => $t->dropForeign(['current_version_id']));
        Schema::dropIfExists('material_versions');
        Schema::dropIfExists('materials');
    }
};
