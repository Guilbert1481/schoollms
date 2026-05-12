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
        Schema::create('school_profiles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('school_id');

    $table->string('school_name')->nullable();
    $table->string('school_logo')->nullable();
    $table->string('school_seal')->nullable();

    $table->string('address')->nullable();
    $table->string('contact_number')->nullable();
    $table->string('mobile_number')->nullable();
    $table->string('fax_number')->nullable();
    $table->string('website')->nullable();
    $table->string('email')->nullable();

    $table->string('motto')->nullable();
    $table->text('vision')->nullable();
    $table->text('mission')->nullable();

    $table->string('head_name')->nullable();
    $table->string('head_title')->nullable();

    $table->string('registrar_name')->nullable();
    $table->string('registrar_title')->nullable();

    $table->year('established_year')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
