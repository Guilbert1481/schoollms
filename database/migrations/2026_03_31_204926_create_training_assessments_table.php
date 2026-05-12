<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id');
            $table->string('title');
            $table->string('type')->nullable(); // quiz, exam, mock exam
            $table->integer('total_items')->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->date('assessment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_assessments');
    }
};