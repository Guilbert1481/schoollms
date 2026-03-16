<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint; // Important: This must be present
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id();

            // The main subject
            $table->foreignId('subject_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // The required subject (explicitly pointing to 'subjects' table)
            $table->foreignId('prerequisite_subject_id')
                  ->constrained('subjects') 
                  ->cascadeOnDelete();

            $table->decimal('minimum_grade', 5, 2)->nullable();
            
            // If true, student CANNOT enroll without passing the prerequisite
            $table->boolean('is_strict')->default(true);

            $table->timestamps();

            // Prevent duplicate prerequisite entries for the same subject
            $table->unique(['subject_id', 'prerequisite_subject_id'], 'sub_pre_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_prerequisites');
    }
};