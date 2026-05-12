<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_course_id');
            $table->string('session_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->integer('max_slots')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->foreign('training_course_id')
                ->references('id')
                ->on('training_courses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};