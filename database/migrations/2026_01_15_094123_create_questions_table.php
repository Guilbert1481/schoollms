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
        Schema::create('questions', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('school_id')->nullable();
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->unsignedBigInteger('lesson_id')->nullable();
    $table->unsignedBigInteger('competency_id')->nullable();

    $table->string('question_type');
    $table->string('difficulty')->nullable();
    $table->integer('points')->nullable();

    $table->text('question');
    $table->text('explanation')->nullable();

    $table->unsignedBigInteger('created_by')->nullable();

    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
