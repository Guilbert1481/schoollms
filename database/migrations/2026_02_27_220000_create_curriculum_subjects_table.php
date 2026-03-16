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
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();

            // Explicitly referencing 'curriculums' table to avoid pluralization errors
            $table->foreignId('curriculum_id')
                  ->constrained('curriculums') 
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Grade level (Basic Ed) or Year level (Higher Ed)
            $table->unsignedInteger('year_level')->nullable(); 

            // 1st, 2nd, Summer, etc.
            $table->string('semester')->nullable(); 

            $table->boolean('is_core')->default(true);
            $table->boolean('is_elective')->default(false);

            // Needed for Higher Ed & Hybrid
            $table->decimal('units', 5, 2)->nullable(); 

            $table->timestamps();

            // Unique constraint to prevent duplicate subjects in the same curriculum
            $table->unique(['curriculum_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_subjects');
    }
};