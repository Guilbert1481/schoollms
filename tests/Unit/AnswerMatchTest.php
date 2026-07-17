<?php

namespace Tests\Unit;

use App\Support\AnswerMatch;
use PHPUnit\Framework\TestCase;

class AnswerMatchTest extends TestCase
{
    public function test_ignores_case_whitespace_and_punctuation(): void
    {
        $this->assertTrue(AnswerMatch::matches('  photosynthesis! ', ['Photosynthesis']));
        $this->assertTrue(AnswerMatch::matches('MOUNT APO', ['mount apo']));
    }

    public function test_allows_a_single_slip_on_longer_answers(): void
    {
        $this->assertTrue(AnswerMatch::matches('photosynthisis', ['Photosynthesis'])); // one-char off
        $this->assertFalse(AnswerMatch::matches('photosyntheeesis', ['Photosynthesis'])); // too far
    }

    public function test_short_answers_require_exact_match(): void
    {
        $this->assertTrue(AnswerMatch::matches('cat', ['cat']));
        $this->assertFalse(AnswerMatch::matches('bat', ['cat'])); // no fuzz on short words
    }

    public function test_blank_and_wrong_answers_do_not_match(): void
    {
        $this->assertFalse(AnswerMatch::matches('', ['Tokyo']));
        $this->assertFalse(AnswerMatch::matches('Paris', ['Tokyo']));
    }

    public function test_matches_any_accepted_alternate(): void
    {
        $this->assertTrue(AnswerMatch::matches('h2o', ['water', 'H2O']));
    }
}
