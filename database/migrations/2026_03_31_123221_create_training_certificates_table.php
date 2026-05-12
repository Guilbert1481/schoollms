<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_enrollment_id');
            $table->string('certificate_number')->nullable();
            $table->date('date_issued')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('training_enrollment_id')
                ->references('id')
                ->on('training_enrollments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificates');
    }
};