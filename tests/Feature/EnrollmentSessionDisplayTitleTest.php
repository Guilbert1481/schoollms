<?php

namespace Tests\Feature;

use App\Models\EnrollmentSetting;
use App\Models\Term;
use Tests\TestCase;

/**
 * The enrollment session's shown name is the single source of truth: the linked
 * Master Data term. Renaming the term (e.g. "2nd Semester" -> "1st Semester")
 * must be reflected without re-saving the session. These are pure in-memory
 * assertions on the accessor (no DB), so they never flake on shared-DB contention.
 */
class EnrollmentSessionDisplayTitleTest extends TestCase
{
    public function test_display_title_follows_the_linked_term_not_the_stored_copy(): void
    {
        $setting = new EnrollmentSetting([
            'title' => '2nd Semester 2026-2027', // stale copy captured at creation
            'name' => '2nd Semester 2026-2027',
        ]);
        // Term was later renamed in Master Data.
        $setting->setRelation('term', new Term(['name' => '1st Semester 2026-2027']));

        $this->assertSame('1st Semester 2026-2027', $setting->display_title);
    }

    public function test_display_title_falls_back_to_stored_copy_for_legacy_rows_without_a_term(): void
    {
        $setting = new EnrollmentSetting([
            'title' => 'LET Review June 2026',
            'name' => 'LET Review June 2026',
        ]);
        $setting->setRelation('term', null); // legacy row, no linked term

        $this->assertSame('LET Review June 2026', $setting->display_title);
    }
}
