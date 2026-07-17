<?php

namespace Tests\Unit;

use App\Services\Tests\OmrSheetTokenService;
use Tests\TestCase;

/**
 * The QR on each OMR sheet is a signed (test, student) token. It must decode
 * back to those ids and reject anything tampered — that's what lets Phase 2
 * (scan/grade) trust which student's sheet it scanned.
 */
class OmrSheetTokenServiceTest extends TestCase
{
    public function test_token_round_trips_to_test_and_student(): void
    {
        $svc = new OmrSheetTokenService;
        $token = $svc->token(12, 345);

        $this->assertSame(['test_id' => 12, 'student_id' => 345], $svc->verify($token));
    }

    public function test_tampered_token_is_rejected(): void
    {
        $svc = new OmrSheetTokenService;
        $token = $svc->token(12, 345);

        $this->assertNull($svc->verify($token.'x'));

        // Swap the student id but keep the old signature → must fail.
        [$t, , $sig] = explode('.', $token);
        $this->assertNull($svc->verify($t.'.999.'.$sig));
    }

    public function test_malformed_tokens_return_null(): void
    {
        $svc = new OmrSheetTokenService;

        $this->assertNull($svc->verify('garbage'));
        $this->assertNull($svc->verify('1.2'));
        $this->assertNull($svc->verify('a.b.c'));
    }
}
