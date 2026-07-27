<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The principal Grade Level Subjects page cascades an education-level category
 * (Preschool / Elementary / Junior High / Senior High — the direct stage children
 * of "Basic Education") into the grade-level dropdown, which narrows to grades
 * under the picked category. Guards that picking a category shows only its grades.
 */
class CurriculumGradeLevelCascadeTest extends TestCase
{
    use RefreshDatabase;

    private function seedTree(): array
    {
        $node = function (string $name, string $type, ?int $parent, int $order) {
            return DB::table('education_nodes')->insertGetId([
                'name' => $name, 'node_type' => $type, 'parent_id' => $parent,
                'order_index' => $order, 'is_offered' => 1, 'is_active' => 1,
            ]);
        };

        $basicEd = $node('Basic Education', 'level', null, 0);
        $elem = $node('Elementary', 'stage', $basicEd, 1);
        $node('Grade 1', 'stage', $elem, 0);
        $node('Grade 2', 'stage', $elem, 1);
        $node('Grade 3', 'stage', $elem, 2);
        $jhs = $node('Junior High', 'stage', $basicEd, 2);
        $node('Grade 7', 'stage', $jhs, 0);
        $node('Grade 8', 'stage', $jhs, 1);

        return ['elementary' => $elem, 'jhs' => $jhs];
    }

    private function principal(): User
    {
        $school = School::factory()->create();

        return User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
    }

    public function test_the_category_dropdown_lists_the_basic_ed_categories(): void
    {
        $this->seedTree();

        $this->actingAs($this->principal())
            ->get(route('principal.curricula-panel.grade-levels'))
            ->assertOk()
            ->assertSee('Education Level…')
            ->assertSee('Elementary')
            ->assertSee('Junior High');
    }

    public function test_grade_dropdown_narrows_to_the_selected_category(): void
    {
        $ids = $this->seedTree();

        $html = $this->actingAs($this->principal())
            ->get(route('principal.curricula-panel.grade-levels', ['category_id' => $ids['elementary']]))
            ->assertOk()
            ->assertSee('Grade 1')
            ->assertSee('Grade 3')
            ->getContent();

        // The grade dropdown must not offer a grade from another category.
        $this->assertStringNotContainsString('>Grade 7<', $html, 'JHS grades must not appear under Elementary');
    }

    public function test_choosing_another_category_switches_the_grades(): void
    {
        $ids = $this->seedTree();

        $this->actingAs($this->principal())
            ->get(route('principal.curricula-panel.grade-levels', ['category_id' => $ids['jhs']]))
            ->assertOk()
            ->assertSee('Grade 7')
            ->assertSee('Grade 8')
            ->assertDontSee('>Grade 1<', false);
    }
}
