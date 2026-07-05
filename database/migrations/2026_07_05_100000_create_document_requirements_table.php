<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registrar-configured document requirements per student type.
 *
 * Each row says: students of {student_type}, in {level/program/year scope},
 * must upload {documents} with their enrollment application. Level, program
 * and year are all nullable — null means "applies to any". The enrollment
 * wizard reads this to render the upload form for transferees / returnees /
 * shifters; uploads land in the existing enrollment_documents table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('student_type', 30); // new|transferee|shifter|returnee|regular|irregular
            $table->foreignId('education_node_id')->nullable()->constrained('education_nodes')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->json('documents'); // ["Transcript of Records", "Good Moral Certificate", ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'student_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
