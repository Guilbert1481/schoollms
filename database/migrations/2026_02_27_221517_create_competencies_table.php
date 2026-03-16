<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompetenciesTable extends Migration
{
    public function up()
    {
        Schema::create('competencies', function (Blueprint $table) {
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

            $table->foreignId('lesson_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->string('code', 50)->nullable();
            $table->string('name', 255);

            $table->text('description')->nullable();

            $table->enum('bloom_level', [
                'remember',
                'understand',
                'apply',
                'analyze',
                'evaluate',
                'create'
            ])->nullable();

            $table->integer('mastery_threshold')->default(75);

            $table->integer('sequence')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->unique(['lesson_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('competencies');
    }
}