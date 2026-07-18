<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provider-agnostic chat client. Loads the superadmin-configured default AI
 * provider and dispatches a system+user prompt to it, returning the assistant's
 * raw text (expected to be JSON). OpenAI / DeepSeek / custom share the OpenAI
 * chat shape; Claude, Gemini, and Ollama each have their own. No provider is
 * hardcoded — everything comes from the ai_providers row.
 */
class AiClient
{
    public function __construct(private AiProvider $provider) {}

    /** The enabled default provider, or null if none is configured. */
    public static function default(): ?self
    {
        $provider = AiProvider::where('is_default', true)->where('is_enabled', true)->first();

        return $provider ? new self($provider) : null;
    }

    public function providerName(): string
    {
        return $this->provider->label;
    }

    /** Send system+user prompts; return the assistant text (expected JSON). */
    public function completeJson(string $system, string $user): string
    {
        $model = (string) ($this->provider->model ?? '');
        $key = $this->provider->api_key; // decrypted by the model cast
        $base = rtrim((string) ($this->provider->base_url ?? ''), '/');

        if ($model === '') {
            throw new RuntimeException("No model is set for {$this->provider->label}. Configure it in Settings → AI.");
        }

        return match ($this->provider->provider) {
            'claude' => $this->claude($base, $key, $model, $system, $user),
            'gemini' => $this->gemini($base, $key, $model, $system, $user),
            'ollama' => $this->ollama($base, $model, $system, $user),
            default => $this->openAiCompatible($base, $key, $model, $system, $user),
        };
    }

    /* ---------------- provider dispatch ---------------- */

    private function openAiCompatible(string $base, ?string $key, string $model, string $system, string $user): string
    {
        $base = $base ?: 'https://api.openai.com/v1';

        $res = Http::withToken($key)->acceptJson()->timeout(90)->post($base.'/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ]);

        $this->assertOk($res);

        return (string) data_get($res->json(), 'choices.0.message.content', '');
    }

    private function claude(string $base, ?string $key, string $model, string $system, string $user): string
    {
        $base = $base ?: 'https://api.anthropic.com';

        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(90)->post($base.'/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);

        $this->assertOk($res);

        return (string) data_get($res->json(), 'content.0.text', '');
    }

    private function gemini(string $base, ?string $key, string $model, string $system, string $user): string
    {
        $base = $base ?: 'https://generativelanguage.googleapis.com';
        $url = $base.'/v1beta/models/'.$model.':generateContent?key='.urlencode((string) $key);

        $res = Http::acceptJson()->timeout(90)->post($url, [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ]);

        $this->assertOk($res);

        return (string) data_get($res->json(), 'candidates.0.content.parts.0.text', '');
    }

    private function ollama(string $base, string $model, string $system, string $user): string
    {
        $base = $base ?: 'http://localhost:11434';

        $res = Http::acceptJson()->timeout(120)->post($base.'/api/chat', [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        $this->assertOk($res);

        return (string) data_get($res->json(), 'message.content', '');
    }

    private function assertOk($res): void
    {
        if (! $res->successful()) {
            $msg = (string) data_get($res->json(), 'error.message', $res->body());

            throw new RuntimeException('AI provider error ('.$res->status().'): '.Str::limit($msg, 300));
        }
    }
}
