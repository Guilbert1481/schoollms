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
        Schema::create('grading_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('grading_system_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            // Quiz, Midterm, Final, Performance Task

            $table->decimal('weight_percentage', 5, 2);
            // Example: 20.00, 30.00, 50.00

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_components');
    }
};
