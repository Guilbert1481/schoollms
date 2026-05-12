<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('training_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_assessment_id');
            $table->foreignId('profile_id');
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('remarks')->nullable(); // Passed, Failed, Absent
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_assessment_scores');
    }
};