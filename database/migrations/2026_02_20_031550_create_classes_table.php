<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassesTable extends Migration
{
    public function up()
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('subject_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('semester_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('teacher_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('section_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->integer('max_students')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['subject_id', 'semester_id', 'section_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('classes');
    }
}