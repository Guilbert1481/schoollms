<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Like Laravel's built-in `encrypted` cast, but TOLERANT of legacy plaintext on
 * read. The vanilla cast calls Crypt::decryptString() unconditionally, so a row
 * written before the column was encrypted throws DecryptException the moment the
 * attribute is accessed — a 500 on every page that reads it, until the operator
 * runs the one-time backfill command.
 *
 * The tolerance is DELIBERATELY NARROW. DecryptException has two structurally
 * different causes, and only one is legacy plaintext:
 *   1. The value is not a well-formed Laravel envelope (base64 of JSON with
 *      iv/value/mac) — i.e. genuine legacy plaintext. This is passed through.
 *   2. The value IS a well-formed envelope but fails MAC verification — i.e.
 *      an APP_KEY rotation without APP_PREVIOUS_KEYS, ciphertext corruption, or
 *      tampering. This is NOT plaintext; silently returning the ~200-char blob
 *      as if it were the ID (and printing it on an ID card / export) would mask
 *      a real crypto failure. So this case is LOGGED (row identity only, never
 *      the value) and rethrown — the same loud failure the vanilla cast gives.
 *
 * {@see looksLikeCiphertext()} draws that line by structure, not by decrypt
 * success, so the discriminator is deterministic in both directions. Writes are
 * identical to the vanilla cast (Crypt::encryptString) except that "" is stored
 * as "" rather than ciphertext-of-empty, matching how the
 * `students:encrypt-government-ids` backfill treats a blank value.
 *
 * NOTE: unlike the built-in cast this does not implement isEncryptedCastable, so
 * Laravel's automatic APP_KEY-rotation re-dirtying and encrypted dirty-comparison
 * do not apply. That is acceptable here because rotation is handled explicitly by
 * the backfill command (P1 key-rotation procedure), not by model re-saves.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedTolerant implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            if ($this->looksLikeCiphertext($value)) {
                // Well-formed ciphertext that will not decrypt: key rotation,
                // corruption, or tamper — never legacy plaintext. Fail loud
                // (as the vanilla cast would) instead of serving the blob, and
                // leave a breadcrumb so a botched rotation is visible. The raw
                // value is deliberately NOT logged.
                Log::warning('EncryptedTolerant: ciphertext-shaped value failed to decrypt', [
                    'model' => $model::class,
                    'attribute' => $key,
                    'id' => $model->getKey(),
                    'school_id' => $attributes['school_id'] ?? null,
                ]);

                throw $e;
            }

            // Genuine legacy plaintext (pre-backfill): pass through quietly.
            return $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Mirror the backfill's blank handling: null/"" stay as-is at rest, so
        // the cast, the backfill, and its isEncrypted() probe agree on what
        // counts as "no value". Everything else is encrypted like the vanilla cast.
        return ($value === null || $value === '') ? $value : Crypt::encryptString($value);
    }

    /**
     * True when the value has the shape of a Laravel encrypted payload — base64
     * of a JSON object carrying iv/value/mac. This distinguishes "ciphertext that
     * failed to decrypt" (alarming) from "never encrypted" (benign legacy
     * plaintext) by structure rather than by decrypt success, so the read path
     * never misclassifies a real crypto failure as plaintext.
     */
    private function looksLikeCiphertext(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
