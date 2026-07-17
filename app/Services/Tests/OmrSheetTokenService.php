<?php

namespace App\Services\Tests;

/**
 * Stateless signed token printed as the QR on each OMR answer sheet. It binds a
 * sheet to (test, student) and is HMAC-signed with the app key, so Phase 2
 * (scan/grade) can decode which student's sheet it is and reject a tampered or
 * hand-crafted code. Nothing is stored — the signature is the proof.
 */
class OmrSheetTokenService
{
    /** "testId.studentId.signature" — compact ASCII for a dense QR. */
    public function token(int $testId, int $studentId): string
    {
        $payload = $testId.'.'.$studentId;

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Verify a scanned token and return ['test_id' => …, 'student_id' => …],
     * or null when the shape or signature is invalid.
     *
     * @return array{test_id:int,student_id:int}|null
     */
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$testId, $studentId, $sig] = $parts;
        if (! ctype_digit($testId) || ! ctype_digit($studentId)) {
            return null;
        }

        if (! hash_equals($this->sign($testId.'.'.$studentId), $sig)) {
            return null;
        }

        return ['test_id' => (int) $testId, 'student_id' => (int) $studentId];
    }

    private function sign(string $payload): string
    {
        return substr(hash_hmac('sha256', "omr:{$payload}", (string) config('app.key')), 0, 16);
    }
}
