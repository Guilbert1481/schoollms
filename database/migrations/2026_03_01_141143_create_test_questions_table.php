<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('test_questions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('test_id');
        $table->unsignedBigInteger('question_id');
        $table->integer('order')->default(1);
        $table->integer('points')->default(1);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('test_questions');
}

};
