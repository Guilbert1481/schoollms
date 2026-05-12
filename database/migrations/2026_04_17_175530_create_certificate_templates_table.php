<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();

            // Link to training type (IMPORTANT)
            $table->foreignId('training_type_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('name')->nullable();

            // Assets
            $table->string('background_image')->nullable();
            $table->string('logo')->nullable();

            // Builder data (positions, fonts, etc.)
            $table->json('elements')->nullable();

            // Optional flags
            $table->boolean('is_default')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};