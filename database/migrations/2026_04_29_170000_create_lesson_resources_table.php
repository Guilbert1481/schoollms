<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained('competencies')->nullOnDelete();

            $table->string('sub_competency')->nullable();
            $table->string('title');

            $table->string('file_path');
            $table->string('file_type', 16);          // video | pdf | ppt
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['program_id', 'subject_id']);
            $table->index(['lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
