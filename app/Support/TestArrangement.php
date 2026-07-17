<?php

namespace App\Support;

/**
 * Deterministic print "arrangement": from a per-test seed, produce a stable sort
 * key for each item so the questionnaire, the answer key, and the OMR answer
 * sheets all render the SAME shuffle. A null seed means the natural (build) order.
 *
 * This shuffles ITEM ORDER only — choices keep their natural order — so the OMR's
 * letter→choice mapping is untouched and grading stays correct. (Choice shuffling
 * would additionally require freezing the shuffled order into each sheet snapshot.)
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
}
