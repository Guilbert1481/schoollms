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
        Schema::create('enrollment_documents', function (Blueprint $table) {
    $table->id();

    $table->foreignId('school_id')->constrained()->cascadeOnDelete();

    $table->foreignId('enrollment_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->string('document_type');
    $table->string('file_path');

    $table->string('status')->default('pending'); 
    // pending, verified, rejected

    $table->timestamp('uploaded_at')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_documents');
    }
};
