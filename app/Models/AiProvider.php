<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A configured AI provider (superadmin-managed, platform-wide). The API key is
 * encrypted at rest via the `encrypted` cast — it is never stored or emitted in
 * plaintext. Use hasKey() to check for a stored key without decrypting it.
 */
class AiProvider extends Model
{
    protected $fillable = [
        'provider',
        'label',
        'api_key',
        'base_url',
        'model',
        'is_enabled',
        'is_default',
        'options',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'options' => 'array',
    ];

    /**
     * The known providers and their sensible defaults. "custom" is an
     * OpenAI-compatible catch-all for anything not listed ("…etc etc").
     *
     * @var array<string, array{label: string, base_url: string, model: string}>
     */
    public const CATALOG = [
        'claude' => ['label' => 'Claude (Anthropic)', 'base_url' => 'https://api.anthropic.com', 'model' => 'claude-opus-4-8'],
        'openai' => ['label' => 'OpenAI (GPT)', 'base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o'],
        'gemini' => ['label' => 'Google Gemini', 'base_url' => 'https://generativelanguage.googleapis.com', 'model' => 'gemini-2.0-flash'],
        'deepseek' => ['label' => 'DeepSeek', 'base_url' => 'https://api.deepseek.com', 'model' => 'deepseek-chat'],
        'ollama' => ['label' => 'Ollama (local)', 'base_url' => 'http://localhost:11434', 'model' => 'llama3'],
        'custom' => ['label' => 'Custom (OpenAI-compatible)', 'base_url' => '', 'model' => ''],
    ];

    /** Ensure a row exists for every known provider (idempotent). */
    public static function syncCatalog(): void
    {
        foreach (self::CATALOG as $slug => $def) {
            self::firstOrCreate(
                ['provider' => $slug],
                [
                    'label' => $def['label'],
                    'base_url' => $def['base_url'] ?: null,
                    'model' => $def['model'] ?: null,
                ]
            );
        }
    }

    /** True when a key is stored, without decrypting it. */
    public function hasKey(): bool
    {
        return filled($this->getRawOriginal('api_key'));
    }
}
