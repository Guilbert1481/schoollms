<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\EducationNode;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assignment Management > Program modals: the Program field is sourced from the
 * Master Data education tree (children of the picked Educational Level node) and
 * the picked node is persisted as programs.education_node_id. Render test also
 * guards the Blade/Alpine rewrite of the picker (2026-07-27).
 */
class ProgramModalEducationLevelTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'admin',
        ]);
    }

    private function makeTree(): array
    {
        $undergrad = EducationNode::create([
            'name' => 'Undergraduate',
            'node_type' => EducationNode::TYPE_LEVEL,
            'is_active' => true,
        ]);
        $nursing = EducationNode::create([
            'name' => 'BS Nursing',
            'parent_id' => $undergrad->id,
            'node_type' => EducationNode::TYPE_STAGE,
            'is_active' => true,
        ]);

        return [$undergrad, $nursing];
    }

    public function test_programs_page_renders_the_education_level_picker(): void
    {
        [$undergrad, $nursing] = $this->makeTree();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assignments.indexPrograms'));

        $response->assertOk();
        $response->assertSee('Educational Level');
        $response->assertSee('-- Select Program --');
        $response->assertSee('programNodePicker');
        $response->assertDontSee('Program Type / Category');

        $nodeIds = collect($response->viewData('educationNodes'))->pluck('id');
        $this->assertTrue($nodeIds->contains($undergrad->id));
        $this->assertTrue($nodeIds->contains($nursing->id));
    }

    public function test_creating_a_program_persists_the_picked_tree_node(): void
    {
        [, $nursing] = $this->makeTree();
        $college = College::create([
            'school_id' => $this->school->id,
            'code' => 'CON',
            'name' => 'College of Nursing',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.assignments.createProgram'), [
                'code' => 'BSN',
                'name' => 'Bachelor of Science in Nursing',
                'college_id' => $college->id,
                'education_node_id' => $nursing->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('programs', [
            'school_id' => $this->school->id,
            'code' => 'BSN',
            'name' => 'Bachelor of Science in Nursing',
            'college_id' => $college->id,
            'education_node_id' => $nursing->id,
        ]);
    }

    public function test_creating_a_program_rejects_an_unknown_tree_node(): void
    {
        $college = College::create([
            'school_id' => $this->school->id,
            'code' => 'CON',
            'name' => 'College of Nursing',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.assignments.createProgram'), [
                'code' => 'BSN',
                'name' => 'Bachelor of Science in Nursing',
                'college_id' => $college->id,
                'education_node_id' => 999999,
            ]);

        $response->assertSessionHasErrors('education_node_id');
        $this->assertDatabaseMissing('programs', ['code' => 'BSN']);
    }
}
