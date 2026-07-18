<?php

namespace App\Support;

/**
 * Deterministic print "arrangement": from a per-test seed, produce stable sort
 * keys so the questionnaire, the answer key, and the OMR answer sheets all render
 * the SAME shuffle. A null seed means the natural (build) order.
 *
 * Two axes, both driven by the one seed:
 *  - orderKey():  the order of QUESTIONS within a section.
 *  - choiceOrder(): the order of CHOICES within a multiple-choice question, which
 *    is what fixes the A–E labelling. Every site that labels choices (print,
 *    answer key, and the frozen sheet snapshot) calls choiceOrder() with the same
 *    seed, so the letter→choice mapping stays identical everywhere and grading —
 *    which reads that mapping off the snapshot — stays correct by construction.
 */
class TestArrangement
{
    /**
     * Sort key for an item whose natural position is $order. With no seed the key
     * is $order itself (natural order); with a seed it is a deterministic hash of
     * the pair, so the same seed always yields the same shuffle and a new seed
     * reshuffles. Grouping (e.g. by question type) is applied by the caller on top.
     */
    public static function orderKey(?int $seed, int $order): int
    {
        return $seed === null ? $order : (int) crc32($seed.'-'.$order);
    }

    /**
     * Deterministic choice order for one question's choices. With no seed the order
     * is by choice id — identical to what the sheet snapshot has always frozen, so
     * an unshuffled test is byte-for-byte unchanged and existing snapshots stay
     * valid. With a seed each question's choices are permuted by a per-question
     * hash (so different questions shuffle differently, and the same question is
     * stable across print/key/snapshot). Caller decides which question types get
     * shuffled — this is only meaningful for multiple choice.
     *
     * @template TChoice
     *
     * @param  \Illuminate\Support\Collection<int, TChoice>  $choices
     * @return \Illuminate\Support\Collection<int, TChoice>
     */
    public static function choiceOrder(?int $seed, int $questionId, $choices)
    {
        if ($seed === null) {
            return $choices->sortBy('id')->values();
        }

        return $choices
            ->sortBy(fn ($c) => (int) crc32($seed.'-c'.$questionId.'-'.$c->id))
            ->values();
    }
}
