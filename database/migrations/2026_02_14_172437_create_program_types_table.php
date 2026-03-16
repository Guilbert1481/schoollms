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
        Schema::create('program_types', function (Blueprint $table) {
    $table->id();

    // Multi-tenant support
    $table->foreignId('school_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->string('name'); // e.g. Regular, Bridging, Short Course
    $table->string('code')->nullable(); // optional short code like REG, BRG
    $table->text('description')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();

    // Prevent duplicate program types per school
    $table->unique(['school_id', 'name']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_types');
    }
};
