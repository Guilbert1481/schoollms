<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\Request;

/**
 * Superadmin → Settings → AI. Saves the platform-wide AI provider configuration.
 * Keys are encrypted by the model cast; a blank key field means "keep the
 * existing key" so the masked value never has to round-trip through the browser.
 */
class AiProviderController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'providers' => ['required', 'array'],
            // http(s) only — this URL is fetched server-side, so an unvalidated
            // scheme is an SSRF vector (SECURITY_PRINCIPLES §16/§18).
            'providers.*.base_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'providers.*.model' => ['nullable', 'string', 'max:191'],
            'providers.*.api_key' => ['nullable', 'string', 'max:500'],
            'providers.*.enabled' => ['nullable'],
            'default_provider' => ['nullable', 'string'],
        ]);

        $default = $data['default_provider'] ?? null;

        foreach ($data['providers'] as $slug => $fields) {
            $provider = AiProvider::where('provider', $slug)->first();
            if (! $provider) {
                continue;
            }

            $provider->base_url = filled($fields['base_url'] ?? null) ? trim($fields['base_url']) : null;
            $provider->model = filled($fields['model'] ?? null) ? trim($fields['model']) : null;

            // Only replace the key when a new value is typed; blank keeps the stored one.
            if (filled($fields['api_key'] ?? null)) {
                $provider->api_key = trim($fields['api_key']);
            }

            $provider->is_enabled = filled($fields['enabled'] ?? null);
            $provider->save();
        }

        // Exactly one default across the whole catalog — reset all, then flag the
        // chosen one (and force it enabled, since the active provider must work).
        // Done catalog-wide so it holds even if the form posted only a subset.
        AiProvider::query()->update(['is_default' => false]);
        if ($default) {
            AiProvider::where('provider', $default)->update(['is_default' => true, 'is_enabled' => true]);
        }

        return redirect()
            ->route('superadmin.settings.index')
            ->with('success', 'AI provider settings saved.');
    }
}
