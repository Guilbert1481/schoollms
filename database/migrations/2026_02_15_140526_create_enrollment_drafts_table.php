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
            Schema::create('enrollment_drafts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
        $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
        $table->json('data')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_drafts');
    }
};
