<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // section_subjects
        if (! Schema::hasTable('section_subjects')) {
            Schema::create('section_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('section_id');
                $table->unsignedBigInteger('subject_id');
                $table->decimal('units', 4, 2)->default(3);
                $table->decimal('hours_per_week', 4, 2)->nullable();
                $table->timestamps();
                $table->unique(['section_id', 'subject_id']);
                $table->index('section_id');
                $table->index('subject_id');
            });
        }

        // teacher_availabilities
        if (! Schema::hasTable('teacher_availabilities')) {
            Schema::create('teacher_availabilities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->string('day_of_week', 12); // monday..sunday
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_available')->default(true);
                $table->timestamps();
                $table->index(['teacher_id', 'day_of_week']);
            });
        }

        // rooms
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->string('name');
                $table->string('code')->nullable();
                $table->unsignedInteger('capacity')->default(40);
                $table->string('type')->nullable(); // lecture, lab, etc
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('school_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('section_subjects');
        Schema::dropIfExists('teacher_availabilities');
        Schema::dropIfExists('rooms');
    }
};
