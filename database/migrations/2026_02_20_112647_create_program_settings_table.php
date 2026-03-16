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
        Schema::create('program_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('enforce_capacity')->default(true);

            $table->boolean('waitlist_enabled')->default(false);

            $table->enum('default_delivery_mode', [
                'onsite',
                'online',
                'hybrid'
            ])->default('onsite');

            $table->boolean('allow_cross_program_enrollment')->default(false);

            $table->timestamps();

            $table->unique(['program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_settings');
    }
};
