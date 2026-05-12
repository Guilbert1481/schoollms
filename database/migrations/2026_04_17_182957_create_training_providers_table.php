<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_providers', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // TESDA, Google, RMC
            $table->text('description')->nullable();

            $table->string('logo')->nullable(); // for certificate branding

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_providers');
    }
};