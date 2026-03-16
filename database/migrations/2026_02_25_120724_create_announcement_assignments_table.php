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
        Schema::create('announcement_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->onDelete('cascade');
            
            // This handles BOTH assignable_type and assignable_id automatically
            $table->morphs('assignable'); 
            
            $table->foreignId('school_id');
            $table->timestamps();
            
            // Optional: Add an index for faster searching
            $table->index(['assignable_type', 'assignable_id'], 'announcement_assignable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_assignments');
    }
};