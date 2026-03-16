<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestQuestionTypePointsTable extends Migration
{
    public function up()
    {
        Schema::create('test_question_type_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('test_id');
            $table->string('question_type', 32);
            $table->integer('points')->nullable();
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests');
            // Optionally, to prevent duplicate types per test:
            // $table->unique(['test_id', 'question_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_question_type_points');
    }
}