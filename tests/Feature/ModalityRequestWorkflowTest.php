<?php

namespace Tests\Feature;

use App\Models\ModalityRequest;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Modality change requests: non-basic-ed only, open for 2 weeks from the
 * official enrollment date, one pending request at a time; the registrar
 * decides, and approval writes the modality onto the enrollment.
 */
class ModalityRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $registrar;

    private User $studentUser;

    private Student $student;

    private int $enrollmentId;

    private int $f2fId;

    private int $onlineId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $this->f2fId = DB::table('modalities')->insertGetId(['name' => 'Face to Face', 'code' => 'f2f', 'created_at' => now(), 'updated_at' => now()]);
        $this->onlineId = DB::table('modalities')->insertGetId(['name' => 'Online', 'code' => 'online', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('school_modalities')->insert([
            ['school_id' => $this->school->id, 'modality_id' => $this->f2fId, 'created_at' => now(), 'updated_at' => now()],
            ['school_id' => $this->school->id, 'modality_id' => $this->onlineId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $this->enrollmentId = $this->makeEnrollment();
    }

    /** An active-year enrolled enrollment; approved now unless overridden. */
    private function makeEnrollment(array $overrides = [], string $termLevel = 'higher_ed'): int
    {
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY-'.uniqid(), 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
            'education_level' => $termLevel,
        ]);
        // One active year per school — the dashboard service resolves by it.
        $ayId = DB::table('academic_years')->where('school_id', $this->school->id)->where('is_active', 1)->value('id')
            ?? DB::table('academic_years')->insertGetId(['school_id' => $this->school->id, 'name' => '2026-2027', 'is_active' => 1]);

        return DB::table('student_enrollments')->insertGetId(array_merge([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId,
            'status' => 'enrolled', 'modality_id' => $this->f2fId,
            'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    public function test_non_basic_student_can_request_within_window(): void
    {
        $this->actingAs($this->studentUser);

        $this->get(route('student.services.modality.index'))->assertOk()->assertSee('Face to Face');

        $this->post(route('student.services.modality.store'), [
            'to_modality_id' => $this->onlineId,
            'reason' => 'Better internet at home',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('modality_requests', [
            'student_enrollment_id' => $this->enrollmentId,
            'from_modality_id' => $this->f2fId,
            'to_modality_id' => $this->onlineId,
            'status' => 'pending',
        ]);
    }

    public function test_basic_ed_student_is_blocked(): void
    {
        DB::table('student_enrollments')->where('id', $this->enrollmentId)->delete();
        $this->makeEnrollment(['education_level' => 'elementary'], termLevel: 'basic_ed');

        $this->actingAs($this->studentUser);

        $this->get(route('student.services.modality.index'))->assertNotFound();
        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId])->assertNotFound();
    }

    public function test_submission_outside_the_two_week_window_is_rejected(): void
    {
        DB::table('student_enrollments')->where('id', $this->enrollmentId)
            ->update(['approved_at' => now()->subDays(15)]);

        $this->actingAs($this->studentUser);

        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId])
            ->assertForbidden();
    }

    public function test_a_second_pending_request_is_rejected(): void
    {
        $this->actingAs($this->studentUser);

        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId]);
        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId])
            ->assertSessionHasErrors('to_modality_id');

        $this->assertSame(1, ModalityRequest::withoutGlobalScopes()->count());
    }

    public function test_registrar_approval_updates_the_enrollment(): void
    {
        $this->actingAs($this->studentUser);
        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId]);
        $request = ModalityRequest::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->registrar);
        $this->put(route('registrar.requests.modality.decide', $request), [
            'action' => 'approve', 'remarks' => 'Approved for the semester',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('modality_requests', ['id' => $request->id, 'status' => 'approved', 'decided_by' => $this->registrar->id]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $this->enrollmentId, 'modality_id' => $this->onlineId]);
    }

    public function test_denial_leaves_the_enrollment_untouched(): void
    {
        $this->actingAs($this->studentUser);
        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId]);
        $request = ModalityRequest::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->registrar);
        $this->put(route('registrar.requests.modality.decide', $request), ['action' => 'deny'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('modality_requests', ['id' => $request->id, 'status' => 'denied']);
        $this->assertDatabaseHas('student_enrollments', ['id' => $this->enrollmentId, 'modality_id' => $this->f2fId]);
    }

    public function test_cross_school_registrar_cannot_decide(): void
    {
        $this->actingAs($this->studentUser);
        $this->post(route('student.services.modality.store'), ['to_modality_id' => $this->onlineId]);
        $request = ModalityRequest::withoutGlobalScopes()->firstOrFail();

        $otherSchool = School::factory()->create();
        $otherRegistrar = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'registrar']);

        $this->actingAs($otherRegistrar);
        $this->put(route('registrar.requests.modality.decide', $request), ['action' => 'approve'])
            ->assertNotFound();

        $this->assertDatabaseHas('modality_requests', ['id' => $request->id, 'status' => 'pending']);
    }
}
