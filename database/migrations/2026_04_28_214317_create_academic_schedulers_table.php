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
        Schema::create('academic_schedulers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('term_id')->nullable()->index();
            $table->json('config')->nullable();
            $table->json('sessions')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'term_id'], 'academic_schedulers_school_term_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_schedulers');
    }
};
