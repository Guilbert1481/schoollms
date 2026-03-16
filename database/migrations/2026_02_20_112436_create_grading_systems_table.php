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
        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            // Example: Basic Ed Percentage, College GPA, Board Review

            $table->enum('type', [
                'percentage',
                'gpa',
                'letter',
                'competency',
                'pass_fail'
            ]);

            $table->decimal('passing_mark', 5, 2)->nullable();
            // Example: 75 (percentage) or 3.00 (GPA)

            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_systems');
    }
};
