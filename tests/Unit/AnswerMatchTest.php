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

    // ---- math-aware path -------------------------------------------------
    // The text normaliser strips all symbols, so before the math path existed
    // "x+1" and "x-1" graded as identical. Math keys compare with operators
    // preserved and never fuzzily.

    public function test_math_answers_keep_their_operators(): void
    {
        $this->assertTrue(AnswerMatch::matches('x+1', ['x+1']));
        $this->assertFalse(AnswerMatch::matches('x-1', ['x+1']));
        $this->assertFalse(AnswerMatch::matches('x+2', ['x+1'])); // no fuzzy slip on math
    }

    public function test_math_answers_ignore_spacing_and_case(): void
    {
        $this->assertTrue(AnswerMatch::matches(' X + 1 ', ['x+1']));
        $this->assertTrue(AnswerMatch::matches('2X²+3', ['2x² + 3']));
    }

    public function test_unicode_math_variants_are_unified(): void
    {
        $this->assertTrue(AnswerMatch::matches('x^2', ['x²']));
        $this->assertTrue(AnswerMatch::matches('x−1', ['x-1']));   // Unicode minus
        $this->assertTrue(AnswerMatch::matches('3×4', ['3*4']));
        $this->assertTrue(AnswerMatch::matches('3·4', ['3*4']));
        $this->assertTrue(AnswerMatch::matches('6÷2', ['6/2']));
        $this->assertTrue(AnswerMatch::matches('1/2', ['½']));
    }

    public function test_math_expressions_with_symbols_are_detected(): void
    {
        $this->assertTrue(AnswerMatch::matches('√(x+1)', ['√(x+1)']));
        $this->assertFalse(AnswerMatch::matches('x+1', ['√(x+1)'])); // missing the root is wrong
        $this->assertTrue(AnswerMatch::matches('y = 2x + 3', ['y=2x+3']));
        $this->assertFalse(AnswerMatch::matches('y = 2x - 3', ['y=2x+3']));
    }

    public function test_hyphenated_words_stay_on_the_text_path(): void
    {
        // These contain "-" but are not math: the forgiving text rules still apply.
        $this->assertTrue(AnswerMatch::matches('mother in law', ['mother-in-law']));
        $this->assertTrue(AnswerMatch::matches('t shirt', ['t-shirt']));
        $this->assertTrue(AnswerMatch::matches('e mail', ['e-mail']));
    }

    public function test_numeric_ranges_are_math_and_require_exactness(): void
    {
        $this->assertTrue(AnswerMatch::matches('3-4', ['3-4']));
        $this->assertFalse(AnswerMatch::matches('3-5', ['3-4']));
    }
}
