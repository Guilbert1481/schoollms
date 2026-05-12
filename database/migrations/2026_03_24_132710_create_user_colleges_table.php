<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_colleges', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('college_id');

            $table->timestamps();

            $table->unique(['user_id', 'college_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('college_id')->references('id')->on('colleges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_colleges');
    }
};
