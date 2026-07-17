<?php

namespace Tests\Unit;

use App\Services\Tests\OmrSheetTokenService;
use Tests\TestCase;

/**
 * The QR on each OMR sheet carries the sheet's lookup token + layout version,
 * signed with the app key. It must decode back to those and reject anything
 * tampered — that's what lets scanning trust which sheet (and layout) it read.
 */
class OmrSheetTokenServiceTest extends TestCase
{
    public function test_token_round_trips_to_sheet_token_and_version(): void
    {
        $svc = new OmrSheetTokenService;
        $qr = $svc->sheetToken('abc123XYZ', 'v1');

        $this->assertSame(['token' => 'abc123XYZ', 'version' => 'v1'], $svc->verifySheet($qr));
    }

    public function test_tampered_token_is_rejected(): void
    {
        $svc = new OmrSheetTokenService;
        $qr = $svc->sheetToken('abc123XYZ', 'v1');

        $this->assertNull($svc->verifySheet($qr.'x'));

        // Swap the lookup token but keep the old signature → must fail.
        [, $version, $sig] = explode('.', $qr);
        $this->assertNull($svc->verifySheet('forged.'.$version.'.'.$sig));
    }

    public function test_malformed_tokens_return_null(): void
    {
        $svc = new OmrSheetTokenService;

        $this->assertNull($svc->verifySheet('garbage'));
        $this->assertNull($svc->verifySheet('a.b'));
        $this->assertNull($svc->verifySheet('a.b.c.d'));
    }
}
