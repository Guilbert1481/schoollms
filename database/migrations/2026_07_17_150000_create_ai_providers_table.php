<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide AI provider configuration (superadmin only). One row per
 * provider (Claude, OpenAI, Gemini, DeepSeek, Ollama, custom …). The API key is
 * stored encrypted at rest (Laravel `encrypted` cast on the model). Exactly one
 * enabled provider is flagged `is_default` — that's the one AI features use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->unique();   // slug: claude, openai, …
            $table->string('label', 64);
            $table->text('api_key')->nullable();          // encrypted ciphertext (long)
            $table->string('base_url')->nullable();       // for Ollama / self-hosted / custom
            $table->string('model')->nullable();          // default model id
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('options')->nullable();          // provider-specific extras
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
