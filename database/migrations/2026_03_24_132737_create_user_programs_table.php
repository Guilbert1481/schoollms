<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_programs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('program_id');

            $table->timestamps();

            $table->unique(['user_id', 'program_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_programs');
    }
};