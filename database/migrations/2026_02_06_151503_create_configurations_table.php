<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship (like your tests table)
            $table->string('owner_type'); // 'App\Models\School' or 'App\Models\User'
            $table->unsignedBigInteger('owner_id');
            
            $table->string('category'); // 'assessment_level', 'difficulty', etc.
            $table->string('label');    // 'Elementary', 'Average', etc.
            $table->string('value');    // 'elementary', 'average', etc.
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['owner_type', 'owner_id', 'category']);
            $table->index(['owner_type', 'owner_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};