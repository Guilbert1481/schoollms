<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonsTable extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('subject_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('topic_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->string('code', 50)->nullable();
            $table->string('name', 150);

            $table->text('description')->nullable();

            $table->integer('sequence')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->unique(['topic_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
}