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
        Schema::create('system_settings', function (Blueprint $table) {
    $table->id();
    $table->integer('session_timeout')->default(15);
    $table->integer('pagination')->default(10);
    $table->string('timezone')->default('Asia/Manila');
    $table->string('date_format')->default('Y-m-d');
    $table->boolean('maintenance_mode')->default(false);
    $table->integer('upload_limit')->default(10);
    $table->string('allowed_file_types')->nullable();
    $table->string('backup_schedule')->default('daily');
    $table->string('school_name')->nullable();
    $table->string('system_logo')->nullable();
    $table->string('smtp_host')->nullable();
    $table->string('sms_api')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
