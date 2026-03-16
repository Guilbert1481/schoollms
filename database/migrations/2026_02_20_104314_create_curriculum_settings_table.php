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
        Schema::create('curriculum_settings', function (Blueprint $table) {
            $table->id();

            // Explicitly point to 'curriculums' to fix the Foreign Key error
            $table->foreignId('curriculum_id')
                  ->constrained('curriculums') 
                  ->cascadeOnDelete();

            // Enrollment behavior
            $table->enum('enrollment_mode', [
                'cohort',      // Auto-assign subjects
                'credit',      // Manual subject selection
                'hybrid'       // Core auto + elective manual
            ])->default('cohort');

            // Academic controls
            $table->boolean('enforce_prerequisites')->default(true);
            $table->boolean('allow_core_override')->default(false);
            $table->boolean('allow_cross_year')->default(true);

            $table->unsignedInteger('max_units')->nullable();
            $table->unsignedInteger('min_units')->nullable();

            // Optional flags for global flexibility
            $table->boolean('auto_assign_core')->default(true);
            $table->boolean('strict_year_level')->default(false);

            $table->timestamps();

            // Ensure one setting record per curriculum
            $table->unique('curriculum_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_settings');
    }
};