<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_academic_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // kinder|elementary|junior_high|senior_high|undergraduate
            $table->string('education_level', 32);
            $table->string('school_name');
            $table->string('school_address')->nullable();
            $table->string('school_type', 16)->nullable();   // public|private|home
            $table->string('last_grade_level', 32)->nullable();
            $table->year('year_started')->nullable();
            $table->year('year_ended')->nullable();
            $table->decimal('gpa', 5, 2)->nullable();
            $table->string('honors')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['student_id', 'education_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_backgrounds');
    }
};
