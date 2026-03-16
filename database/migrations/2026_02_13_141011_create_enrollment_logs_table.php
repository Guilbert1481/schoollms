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
        Schema::create('enrollment_logs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('school_id')->constrained()->cascadeOnDelete();

    $table->foreignId('enrollment_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->string('old_status');
    $table->string('new_status');

    $table->foreignId('changed_by')
          ->constrained('users')
          ->restrictOnDelete();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_logs');
    }
};
