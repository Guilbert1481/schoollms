<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\ClearanceItem;
use App\Models\ClearanceSignatory;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Clearance: starting one generates a row per signatory (defaults seeded per
 * school; Subject Teachers expands per (subject, teacher)); the registrar
 * signs items off and the clearance completes when every row is cleared.
 * Signatories are a registrar-editable settings list scoped by education level.
 */
class ClearanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $registrar;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private int $enrollmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'first_name' => 'Pedro', 'last_name' => 'Reyes']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);

        // Higher-ed enrollment with one subject class taught by Pedro.
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'education_level' => 'higher_ed',
        ]);
        $ayId = DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1]);
        $sectionId = DB::table('sections')->insertGetId(['school_id' => $this->school->id, 'term_id' => $termId, 'name' => 'BSIT', 'year_level' => 1]);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $this->school->id, 'code' => 'SCI', 'name' => 'Science']);
        $classId = DB::table('classes')->insertGetId(['school_id' => $this->school->id, 'subject_id' => $subjectId, 'term_id' => $termId, 'teacher_id' => $this->teacher->id, 'section_id' => $sectionId, 'code' => 'SCI-A']);

        $this->enrollmentId = DB::table('student_enrollments')->insertGetId([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId, 'section_id' => $sectionId,
            'status' => 'enrolled', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_enrollment_subjects')->insert(['student_enrollment_id' => $this->enrollmentId, 'class_id' => $classId, 'subject_id' => $subjectId, 'status' => 'enrolled']);
    }

    private function startClearance(): Clearance
    {
        $this->actingAs($this->studentUser)->post(route('student.services.clearance.store'), [
            'purpose' => 'End of Term',
        ]);

        return Clearance::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_starting_a_clearance_seeds_defaults_and_expands_subject_teachers(): void
    {
        $clearance = $this->startClearance();

        // 4 department defaults + 1 subject-teacher row for Science — Pedro.
        $labels = ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->pluck('label');
        $this->assertCount(5, $labels);
        $this->assertTrue($labels->contains('Finance / Cashier'));
        $this->assertTrue($labels->contains('Registrar'));
        $this->assertTrue($labels->contains('Guidance'));
        $this->assertTrue($labels->contains('Librarian'));
        $this->assertTrue($labels->contains('Science — Pedro Reyes'));

        // Defaults were seeded once for the school, deletable later.
        $this->assertSame(5, ClearanceSignatory::withoutGlobalScopes()->where('school_id', $this->school->id)->count());
    }

    public function test_only_one_open_clearance_per_enrollment(): void
    {
        $this->startClearance();

        $this->actingAs($this->studentUser)->post(route('student.services.clearance.store'), [
            'purpose' => 'Transfer',
        ])->assertSessionHasErrors('purpose');

        $this->assertSame(1, Clearance::withoutGlobalScopes()->count());
    }

    public function test_registrar_clears_every_item_and_the_clearance_completes(): void
    {
        $clearance = $this->startClearance();

        $this->actingAs($this->registrar);
        $this->get(route('registrar.clearances.show', $clearance))->assertOk()->assertSee('Science — Pedro Reyes');

        foreach (ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->get() as $item) {
            $this->put(route('registrar.clearances.items.update', [$clearance, $item]), ['action' => 'cleared'])
                ->assertSessionHas('success');
        }

        $fresh = $clearance->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_a_hold_keeps_the_clearance_in_progress(): void
    {
        $clearance = $this->startClearance();
        $item = ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->firstOrFail();

        $this->actingAs($this->registrar);
        $this->put(route('registrar.clearances.items.update', [$clearance, $item]), [
            'action' => 'hold', 'remarks' => 'Unpaid balance',
        ]);

        $this->assertDatabaseHas('clearance_items', ['id' => $item->id, 'status' => 'hold', 'remarks' => 'Unpaid balance']);
        $this->assertSame('in_progress', $clearance->fresh()->status);
    }

    public function test_signatory_settings_crud_and_level_scoping(): void
    {
        $this->actingAs($this->registrar);

        // Opening the settings page seeds the defaults.
        $this->get(route('registrar.settings.clearance-signatories.index'))->assertOk()->assertSee('Subject Teachers');
        $this->assertSame(5, ClearanceSignatory::withoutGlobalScopes()->count());

        // Add a higher-ed-only signatory, then delete the Librarian.
        $this->post(route('registrar.settings.clearance-signatories.store'), [
            'name' => 'Laboratory Custodian', 'type' => 'department', 'applies_to' => 'higher',
        ])->assertSessionHas('success');

        $librarian = ClearanceSignatory::withoutGlobalScopes()->where('name', 'Librarian')->firstOrFail();
        $this->delete(route('registrar.settings.clearance-signatories.destroy', $librarian))->assertSessionHas('success');

        // A new clearance reflects the edited list: no Librarian, plus the lab.
        $clearance = $this->startClearance();
        $labels = ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->pluck('label');
        $this->assertFalse($labels->contains('Librarian'));
        $this->assertTrue($labels->contains('Laboratory Custodian'));
    }

    public function test_basic_ed_only_signatories_are_excluded_for_higher_ed_students(): void
    {
        $this->actingAs($this->registrar);
        $this->get(route('registrar.settings.clearance-signatories.index')); // seed
        $this->post(route('registrar.settings.clearance-signatories.store'), [
            'name' => 'Homeroom Adviser', 'type' => 'department', 'applies_to' => 'basic',
        ]);

        $clearance = $this->startClearance();  // higher-ed student

        $labels = ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->pluck('label');
        $this->assertFalse($labels->contains('Homeroom Adviser'));
    }

    public function test_cross_school_registrar_cannot_see_or_touch_a_clearance(): void
    {
        $clearance = $this->startClearance();
        $item = ClearanceItem::withoutGlobalScopes()->where('clearance_id', $clearance->id)->firstOrFail();

        $otherSchool = School::factory()->create();
        $otherRegistrar = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'registrar']);

        $this->actingAs($otherRegistrar);
        $this->get(route('registrar.clearances.show', $clearance))->assertNotFound();
        $this->put(route('registrar.clearances.items.update', [$clearance, $item]), ['action' => 'cleared'])->assertNotFound();

        $this->assertDatabaseHas('clearance_items', ['id' => $item->id, 'status' => 'pending']);
    }
}
