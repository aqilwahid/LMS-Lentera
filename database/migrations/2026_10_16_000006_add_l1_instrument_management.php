<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('l1_instrument_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::table('l1_instruments', function (Blueprint $table) {
            // Existing instruments remain shared, read-only reference versions.
            $table->foreignId('l1_instrument_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->string('creation_key', 64)->nullable()->unique();
            $table->string('creation_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('l1_instruments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('l1_instrument_set_id');
            $table->dropUnique(['creation_key']);
            $table->dropColumn(['revision', 'creation_key', 'creation_hash']);
        });
        Schema::dropIfExists('l1_instrument_sets');
    }
};
