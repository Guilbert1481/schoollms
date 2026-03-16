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
    Schema::create('announcements', function (Blueprint $table) {
        $table->id();

        $table->string('title');
        $table->longText('content');

        // Targeting system
        $table->string('target_type')->nullable(); 
        // example: all, role, department, program, user

        $table->unsignedBigInteger('target_id')->nullable();
        // optional: role_id, department_id, user_id, etc.

        // Who created it
        $table->foreignId('created_by')
              ->constrained('users')
              ->cascadeOnDelete();

        $table->timestamp('published_at')->nullable();
        $table->timestamp('expires_at')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
