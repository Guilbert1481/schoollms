<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_course_id');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('training_course_id')
                ->references('id')
                ->on('training_courses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_materials');
    }
};