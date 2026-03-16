<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_grade_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_grade_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('grading_component_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('score', 8, 2)->nullable();

            $table->timestamps();

            // Manually name the unique index to stay under the 64-character limit
            $table->unique(
                ['class_grade_id', 'grading_component_id'], 
                'cgc_grade_component_unique' 
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_grade_components');
    }
};