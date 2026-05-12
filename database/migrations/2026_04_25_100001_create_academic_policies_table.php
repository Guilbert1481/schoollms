<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // kinder | elementary | junior_high | senior_high | undergraduate | graduate
            $table->string('education_level', 32);

            $table->foreignId('program_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->nullable()
                  ->constrained('terms')
                  ->cascadeOnDelete();

            $table->decimal('min_units', 5, 2)->nullable();
            $table->decimal('max_units', 5, 2)->nullable();
            $table->integer('max_subjects')->nullable();
            $table->decimal('overload_threshold_units', 5, 2)->nullable();
            $table->integer('max_section_capacity_override')->nullable();

            // Payment gating
            $table->boolean('requires_payment_to_enrol')->default(false);
            $table->decimal('min_payment_percent', 5, 2)->default(0);

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // Resolution lookup: school → +level → +program → +term
            $table->index(
                ['school_id', 'education_level', 'program_id', 'term_id'],
                'idx_policy_resolution'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policies');
    }
};
