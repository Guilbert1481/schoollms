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
        Schema::create('school_modules', function (Blueprint $table) {
            $table->id();
            // Link to your existing schools table
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            
            // The unique name of the feature (e.g., 'library', 'exams')
            $table->string('module_name');
            
            // The toggle switch
            $table->boolean('is_enabled')->default(true);
            
            $table->timestamps();

            // Ensure each school only has one entry per module
            $table->unique(['school_id', 'module_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_modules');
    }
};
