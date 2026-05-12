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
        Schema::create('enrollment_types', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Regular, Seminar, Training, Review
    $table->string('code')->unique(); // REG, SEM, TRN, REV
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_types');
    }
};
