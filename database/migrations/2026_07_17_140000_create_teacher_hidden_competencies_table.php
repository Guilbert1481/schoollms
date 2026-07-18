<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-teacher hidden competencies. When a teacher "hides" a Course-Architect
 * competency it disappears from their own Lessons view only (so they can author
 * their own in its place) — it is never removed for anyone else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_hidden_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_hidden_competencies');
    }
};
