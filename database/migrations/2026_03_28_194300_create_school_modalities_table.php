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
    Schema::create('school_modalities', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->unsignedBigInteger('modality_id');
        $table->timestamps();

        $table->foreign('school_id')
              ->references('id')
              ->on('schools')
              ->cascadeOnDelete();

        $table->foreign('modality_id')
              ->references('id')
              ->on('modalities')
              ->cascadeOnDelete();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_modalities');
    }
};
