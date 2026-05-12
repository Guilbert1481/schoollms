<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_file_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['edit', 'replace', 'rename'])->default('edit');
            $table->string('summary', 255)->nullable();
            $table->timestamps();

            $table->index(['drive_file_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_file_edits');
    }
};
