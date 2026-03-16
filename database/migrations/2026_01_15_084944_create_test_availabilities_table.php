<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->boolean('is_published')->default(false);
            $table->boolean('show_score_immediately')->default(false);
            $table->boolean('show_correct_answers')->default(false);
            $table->boolean('allow_retakes')->default(false);
            $table->boolean('allow_practice')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_availabilities');
    }
};
