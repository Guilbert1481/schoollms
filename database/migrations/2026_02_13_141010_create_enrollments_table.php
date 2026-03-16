<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('school_id')->constrained()->cascadeOnDelete();

    $table->foreignId('student_id')
          ->constrained('users')
          ->cascadeOnDelete();

    $table->foreignId('program_id')
          ->constrained()
          ->restrictOnDelete();

    $table->foreignId('academic_term_id')
          ->constrained()
          ->restrictOnDelete();

    $table->foreignId('campus_id')
          ->constrained()
          ->restrictOnDelete();

    $table->string('enrollment_type');
    $table->string('enrollment_status')->default('draft');

    $table->foreignId('approved_by')
          ->nullable()
          ->constrained('users')
          ->nullOnDelete();

    $table->timestamp('approved_at')->nullable();

    $table->text('remarks')->nullable();

    $table->timestamps();

    // 🔒 One program per term per school
    $table->unique(['student_id', 'academic_term_id', 'school_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
