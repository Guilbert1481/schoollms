<?php

namespace App\Services\Tests;

/**
 * Signs the QR printed on each OMR sheet. The QR carries the sheet's unguessable
 * lookup token plus the layout version, HMAC-signed with the app key. Scanning
 * decodes to the sheet (which resolves student + test + section) and is rejected
 * if tampered or hand-crafted. The sheet row is the record of truth; the
 * signature just makes a forged code useless.
 */
class OmrSheetTokenService
{
    /** "lookupToken.version.signature" — compact ASCII for a dense QR. */
    public function sheetToken(string $lookupToken, string $version): string
    {
        $payload = $lookupToken.'.'.$version;

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Verify a scanned QR payload and return ['token' => …, 'version' => …],
     * or null when the shape or signature is invalid.
     *
     * @return array{token:string,version:string}|null
     */
    public function verifySheet(string $payload): ?array
    {
        $parts = explode('.', $payload);
        if (count($parts) !== 3) {
            return null;
        }

        [$token, $version, $sig] = $parts;
        if ($token === '' || $version === '') {
            return null;
        }

        if (! hash_equals($this->sign($token.'.'.$version), $sig)) {
            return null;
        }

        return ['token' => $token, 'version' => $version];
    }

    private function sign(string $payload): string
    {
        return substr(hash_hmac('sha256', "omr-sheet:{$payload}", (string) config('app.key')), 0, 16);
    }
}
