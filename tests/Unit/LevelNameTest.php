<?php

namespace Tests\Unit;

use App\Support\LevelName;
use PHPUnit\Framework\TestCase;

class LevelNameTest extends TestCase
{
    public function test_key_strips_trailing_qualifier_and_lowercases(): void
    {
        $this->assertSame('grade 11', LevelName::key('Grade 11 (Core)'));
        $this->assertSame('year 4', LevelName::key('year 4'));
        $this->assertSame('grade 3', LevelName::key('Grade   3'));
    }

    public function test_kindergarten_maps_to_the_seeded_kinder_key(): void
    {
        $this->assertSame('kinder', LevelName::key('Kindergarten'));
        $this->assertSame('kinder', LevelName::key('Kinder'));
    }

    public function test_arbitrary_offered_levels_map_to_their_own_key(): void
    {
        $this->assertSame('toddler', LevelName::key('Toddler'));
        $this->assertSame('nursery', LevelName::key('Nursery'));
    }

    public function test_display_cleans_but_preserves_case(): void
    {
        $this->assertSame('Grade 11', LevelName::display('Grade 11 (Core)'));
        $this->assertSame('Toddler', LevelName::display('Toddler'));
    }

    public function test_blank_name_has_no_key(): void
    {
        $this->assertNull(LevelName::key('   '));
    }
}
