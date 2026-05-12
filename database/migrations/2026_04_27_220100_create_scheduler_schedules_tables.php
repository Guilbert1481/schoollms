<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->string('name')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->decimal('score', 8, 2)->default(0);
                $table->boolean('is_active')->default(false);
                $table->json('meta')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'term_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('schedule_sessions')) {
            Schema::create('schedule_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedBigInteger('section_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->string('day_of_week', 12);
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status', 16)->default('valid'); // valid|conflict|adjusted
                $table->json('conflict_reasons')->nullable();
                $table->timestamps();
                $table->index('schedule_id');
                $table->index(['section_id', 'day_of_week']);
                $table->index(['teacher_id', 'day_of_week']);
                $table->index(['room_id', 'day_of_week']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_sessions');
        Schema::dropIfExists('schedules');
    }
};
