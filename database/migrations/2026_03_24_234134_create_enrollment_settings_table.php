<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_year_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->boolean('is_open')->default(false);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // Only one active enrollment setting at a time
            $table->unique(['academic_year_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_settings');
    }
};