<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            // Denormalised for fast picker / conflict queries
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('room')->nullable();

            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');

            // Per-session capacity override; falls back to classes.max_students if null
            $table->integer('capacity')->nullable();

            // scheduled | cancelled | rescheduled
            $table->string('status', 16)->default('scheduled');

            $table->timestamps();

            $table->index(['class_id', 'meeting_date']);
            $table->index(['meeting_date', 'start_time', 'end_time'], 'idx_session_time');
            $table->index(['teacher_id', 'meeting_date'], 'idx_session_teacher_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
