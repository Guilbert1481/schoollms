<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sections', function (Blueprint $table) {

            $table->id();

            $table->foreignId('school_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('program_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('semester_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('name'); // A, B, 1A, etc.

            $table->integer('year_level'); // 1,2,3,4

            $table->foreignId('adviser_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['program_id', 'semester_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sections');
    }
};
