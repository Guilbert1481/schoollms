<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->nullable();
            $table->string('course_name');
            $table->string('course_type')->nullable(); // review, seminar, training, workshop, certification
            $table->text('description')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->integer('duration_hours')->nullable();
            $table->string('delivery_mode')->nullable(); // online, f2f, hybrid
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};