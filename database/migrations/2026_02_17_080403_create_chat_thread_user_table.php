<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_thread_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_thread_id')
                  ->constrained('chat_threads')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['chat_thread_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_thread_user');
    }
};
