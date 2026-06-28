<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot connecting basic-ed grade levels (education_nodes of node_type='stage')
 * to subjects. Managed by Principals via the principal curricula panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_level_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('education_node_id')
                  ->constrained('education_nodes')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['education_node_id', 'subject_id'], 'grade_level_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_level_subjects');
    }
};
