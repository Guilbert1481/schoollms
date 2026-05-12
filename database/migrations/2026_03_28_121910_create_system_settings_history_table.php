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
        Schema::create('system_settings_history', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('school_id');
    $table->unsignedBigInteger('updated_by'); // user who changed settings

    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings_history');
    }
};
