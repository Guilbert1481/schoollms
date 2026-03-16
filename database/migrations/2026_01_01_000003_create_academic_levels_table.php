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
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();

            $table->string('name'); 
            // Example: Grade 1, Grade 2, Year 1, Level 1

            $table->integer('sequence_order');
            // Controls progression order (1,2,3,4...)

            $table->enum('type', [
                'basic',
                'higher',
                'training',
                'review'
            ]);

            $table->timestamps();

            $table->unique(['name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
