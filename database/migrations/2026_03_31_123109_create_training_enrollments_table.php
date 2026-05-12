<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainee_id');
            $table->unsignedBigInteger('training_session_id');
            $table->date('enrollment_date')->nullable();
            $table->string('status')->default('enrolled');
            $table->timestamps();

            $table->foreign('trainee_id')
                ->references('id')
                ->on('trainees')
                ->onDelete('cascade');

            $table->foreign('training_session_id')
                ->references('id')
                ->on('training_sessions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};