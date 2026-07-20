<?php

namespace Tests\Feature;

use App\Models\FinanceFeeSetup;
use App\Models\GradeComponent;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Academics\ReportCardService;
use App\Services\Academics\SectioningService;
use App\Services\Finance\InvoiceService;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The complete basic-ed story, end to end:
 *
 *   grade section created + published → student enrolls (no section) →
 *   invoice from fee setups → payment settles → officially enrolled →
 *   registrar sections the student (workbench) → section classes built
 *   (one per learning area, with teachers + adviser) → adviser marks daily
 *   attendance → subject teacher posts period grades → report card shows
 *   them.
 *
 * Every hop crosses a real HTTP endpoint or the same service the production
 * flow uses — this test is the contract that the chain stays connected.
 */
class BasicEdFlowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $registrar;

    private User $finance;

    private User $adviser;

    private User $englishTeacher;

    private Student $student;

    private int $termId;

    private int $ayId;

    private int $nodeId;

    private int $mathId;

    private int $englishId;

    private array $componentIds;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var School $school */
        $school = School::factory()->create();
        $this->school = $school;
        $sid = $school->id;

        $this->admin = User::factory()->create(['school_id' => $sid, 'role' => 'admin']);
        $this->registrar = User::factory()->create(['school_id' => $sid, 'role' => 'registrar']);
        $this->finance = User::factory()->create(['school_id' => $sid, 'role' => 'finance_manager']);
        $this->adviser = User::factory()->create(['school_id' => $sid, 'role' => 'teacher']);
        $this->englishTeacher = User::factory()->create(['school_id' => $sid, 'role' => 'teacher']);

        $this->termId = DB::table('terms')->insertGetId([
            'school_id' => $sid, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'name' => 'Basic Ed 2026-2027', 'education_level' => 'basic_ed',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $this->ayId = DB::table('academic_years')->insertGetId(['school_id' => $sid, 'name' => '2026-2027']);

        // Grade 5 with a two-subject curriculum.
        $this->nodeId = DB::table('education_nodes')->insertGetId([
            'name' => 'Grade 5', 'node_type' => 'stage', 'is_offered' => 1, 'is_active' => 1,
        ]);
        $this->mathId = DB::table('subjects')->insertGetId(['school_id' => $sid, 'code' => 'G5-MATH', 'name' => 'Mathematics 5']);
        $this->englishId = DB::table('subjects')->insertGetId(['school_id' => $sid, 'code' => 'G5-ENG', 'name' => 'English 5']);
        foreach ([$this->mathId, $this->englishId] as $subjectId) {
            DB::table('grade_level_subjects')->insert([
                'education_node_id' => $this->nodeId, 'subject_id' => $subjectId, 'is_active' => 1,
            ]);
        }

        // WW/PT/QA grading scheme for the matching academic level.
        $levelId = DB::table('academic_levels')->insertGetId(['school_id' => $sid, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic']);
        $setting = GradingSetting::create(['school_id' => $sid, 'academic_level_id' => $levelId, 'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0]);
        foreach ([['WW', 30], ['PT', 40], ['QA', 30]] as $i => [$name, $w]) {
            GradeComponent::create(['school_id' => $sid, 'grading_setting_id' => $setting->id, 'name' => $name, 'weight' => $w, 'sort_order' => $i]);
        }
        $this->componentIds = $setting->components()->pluck('id', 'name')->all();

        // Grade 5 tuition so the enrollment is billable.
        FinanceFeeSetup::create([
            'school_id' => $sid, 'academic_year_id' => $this->ayId, 'education_node_id' => $this->nodeId,
            'fee_type' => 'tuition', 'code' => 'G5-TUI', 'name' => 'Grade 5 Tuition',
            'billing_basis' => 'fixed', 'amount' => 5000, 'is_active' => 1,
        ]);

        $studentUser = User::factory()->create(['school_id' => $sid, 'role' => 'student']);
        $this->student = Student::create([
            'school_id' => $sid, 'user_id' => $studentUser->id, 'student_number' => 'S-0001',
            'first_name' => 'Ana', 'last_name' => 'Santos',
        ]);
    }

    public function test_the_full_basic_ed_chain_from_section_to_report_card(): void
    {
        /* 1 — Admission creates + publishes the Grade 5 section. */
        $this->actingAs($this->admin)->post(route('admission.sections.store'), [
            'term_id' => $this->termId, 'education_node_id' => $this->nodeId,
            'name' => 'G5-A', 'capacity' => 40,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $sectionId = (int) DB::table('sections')->where('name', 'G5-A')->value('id');
        $this->assertDatabaseHas('sections', [
            'id' => $sectionId, 'education_node_id' => $this->nodeId, 'program_id' => null, 'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->post(route('admission.sections.publish', $sectionId))->assertRedirect();

        /* 2 — The student's enrollment arrives at billing (docs complied). */
        $enrollment = StudentEnrollment::create([
            'school_id' => $this->school->id, 'student_id' => $this->student->id,
            'academic_year_id' => $this->ayId, 'term_id' => $this->termId,
            'education_node_id' => $this->nodeId, 'education_level' => 'elementary',
            'status' => 'sent_billing', 'billing_cleared_as' => 'assessed',
        ]);

        /* 3 — Finance: invoice from the fee setup, then payment settles it. */
        $invoice = app(InvoiceService::class)->generateForEnrollment($enrollment->fresh('student'), $this->finance->id);
        $this->assertNotNull($invoice, 'The enrollment should be invoiceable from the Grade 5 fee setup.');
        $this->assertEquals(5000.0, (float) $invoice->total_amount);

        app(PaymentService::class)->recordInvoicePayment($this->finance, $invoice, 5000.0, 'cash', 'OR-1');
        $this->assertSame('enrolled', $enrollment->fresh()->status, 'Settling the invoice should officially enroll.');
        $this->assertNull($enrollment->fresh()->section_id, 'Enrollment happens without a section.');

        /* 4 — Registrar sections the student on the workbench. */
        $this->actingAs($this->registrar)
            ->get(route('registrar.sectioning.index', ['term_id' => $this->termId]))
            ->assertOk()->assertSeeText('Santos, Ana');

        $this->actingAs($this->registrar)->post(route('registrar.sectioning.assign'), [
            'term_id' => $this->termId, 'section_id' => $sectionId, 'enrollment_ids' => [$enrollment->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($sectionId, (int) $enrollment->fresh()->section_id);

        /* 5 — Registrar builds the section's classes + adviser in one form. */
        $this->actingAs($this->registrar)
            ->get(route('registrar.section-classes.show', $sectionId))
            ->assertOk()->assertSeeText('Mathematics 5')->assertSeeText('English 5');

        $this->actingAs($this->registrar)->post(route('registrar.section-classes.store', $sectionId), [
            'adviser_id' => $this->adviser->id,
            'teachers' => [
                $this->mathId => $this->adviser->id,
                $this->englishId => $this->englishTeacher->id,
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('sections', ['id' => $sectionId, 'adviser_id' => $this->adviser->id]);
        $mathClassId = (int) DB::table('classes')->where([
            'subject_id' => $this->mathId, 'section_id' => $sectionId, 'term_id' => $this->termId,
            'teacher_id' => $this->adviser->id,
        ])->value('id');
        $this->assertGreaterThan(0, $mathClassId);
        $this->assertDatabaseHas('classes', [
            'subject_id' => $this->englishId, 'section_id' => $sectionId, 'teacher_id' => $this->englishTeacher->id,
        ]);

        /* 6 — The adviser marks daily attendance for the sectioned roster. */
        $this->actingAs($this->adviser)->post(route('teacher.attendance.store'), [
            'type' => 'daily', 'date' => '2026-07-01', 'section_id' => $sectionId,
            'marks' => [['student_id' => $this->student->id, 'status' => 'present']],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id, 'section_id' => $sectionId, 'scope' => 'daily', 'status' => 'present',
        ]);

        /* 7 — The Math teacher posts 1st-grading scores. */
        $payload = [
            'class_id' => $mathClassId, 'period' => 1,
            'scores' => [$this->student->id => [
                $this->componentIds['WW'] => 90, $this->componentIds['PT'] => 80, $this->componentIds['QA'] => 70,
            ]],
        ];
        $this->actingAs($this->adviser)->post(route('teacher.gradebook.post'), $payload)->assertRedirect();

        $this->assertDatabaseHas('report_card_grades', [
            'student_id' => $this->student->id, 'education_node_id' => $this->nodeId,
            'subject_id' => $this->mathId, 'grading_period' => 1,
        ]);

        /* 8 — The report card shows the grade. */
        $card = app(ReportCardService::class)->build($this->student->fresh());

        $this->assertTrue($card['is_basic']);
        $this->assertTrue($card['has_context']);
        $mathRow = collect($card['rows'])->firstWhere('subject_id', $this->mathId);
        $this->assertNotNull($mathRow, 'Math must appear as a learning area on the report card.');
        $this->assertEqualsWithDelta(80.0, (float) $mathRow['cells'][1]['grade_raw'], 0.01); // 90×.3 + 80×.4 + 70×.3
    }

    public function test_auto_distribution_balances_sections_and_only_applies_on_confirm(): void
    {
        // Two published G5 sections (capacity 2 each) and three unsectioned students.
        $sections = [];
        foreach (['G5-A', 'G5-B'] as $name) {
            $sections[$name] = DB::table('sections')->insertGetId([
                'school_id' => $this->school->id, 'term_id' => $this->termId,
                'education_node_id' => $this->nodeId, 'name' => $name,
                'capacity' => 2, 'is_active' => 1, 'status' => 'published',
            ]);
        }
        $enrollmentIds = [];
        foreach ([['Cruz', 'Alia'], ['Reyes', 'Ben'], ['Uy', 'Carla']] as [$last, $first]) {
            $stu = Student::create([
                'school_id' => $this->school->id, 'student_number' => 'S-'.$last,
                'first_name' => $first, 'last_name' => $last,
            ]);
            $enrollmentIds[] = (int) DB::table('student_enrollments')->insertGetId([
                'school_id' => $this->school->id, 'student_id' => $stu->id,
                'academic_year_id' => $this->ayId, 'term_id' => $this->termId,
                'education_node_id' => $this->nodeId, 'status' => 'enrolled',
            ]);
        }

        // The proposal alone writes nothing.
        $plan = app(SectioningService::class)->plan($this->school->id, $this->termId, $this->nodeId, 'alphabetical');
        $this->assertCount(3, $plan['placements']);
        $this->assertSame([], $plan['leftover']);
        $this->assertSame(0, DB::table('student_enrollments')->whereNotNull('section_id')->count());

        // Balanced: neither section exceeds capacity, sizes differ by at most one.
        $bySection = array_count_values($plan['placements']);
        $this->assertLessThanOrEqual(1, abs(($bySection[$sections['G5-A']] ?? 0) - ($bySection[$sections['G5-B']] ?? 0)));

        // Applying via the endpoint sections everyone.
        $this->actingAs($this->registrar)->post(route('registrar.sectioning.assign'), [
            'term_id' => $this->termId, 'placements' => $plan['placements'],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(3, DB::table('student_enrollments')->whereIn('id', $enrollmentIds)->whereNotNull('section_id')->count());
    }

    public function test_a_student_cannot_be_placed_into_a_full_or_wrong_grade_section(): void
    {
        $sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->school->id, 'term_id' => $this->termId,
            'education_node_id' => $this->nodeId, 'name' => 'G5-A',
            'capacity' => 1, 'is_active' => 1, 'status' => 'published',
        ]);
        $otherNode = DB::table('education_nodes')->insertGetId([
            'name' => 'Grade 6', 'node_type' => 'stage', 'is_offered' => 1, 'is_active' => 1,
        ]);

        $make = function (int $nodeId, string $last) {
            $stu = Student::create([
                'school_id' => $this->school->id, 'student_number' => 'S-'.$last,
                'first_name' => 'X', 'last_name' => $last,
            ]);

            return (int) DB::table('student_enrollments')->insertGetId([
                'school_id' => $this->school->id, 'student_id' => $stu->id,
                'academic_year_id' => $this->ayId, 'term_id' => $this->termId,
                'education_node_id' => $nodeId, 'status' => 'enrolled',
            ]);
        };

        // Wrong grade: a Grade 6 student cannot land in the Grade 5 section.
        $g6 = $make($otherNode, 'WrongGrade');
        $this->actingAs($this->registrar)->post(route('registrar.sectioning.assign'), [
            'term_id' => $this->termId, 'section_id' => $sectionId, 'enrollment_ids' => [$g6],
        ])->assertSessionHasErrors('placements');
        $this->assertNull(DB::table('student_enrollments')->where('id', $g6)->value('section_id'));

        // Capacity: the second Grade 5 student does not fit a capacity-1 section.
        $first = $make($this->nodeId, 'Fits');
        $second = $make($this->nodeId, 'Overflows');
        $this->actingAs($this->registrar)->post(route('registrar.sectioning.assign'), [
            'term_id' => $this->termId, 'section_id' => $sectionId, 'enrollment_ids' => [$first],
        ])->assertSessionHas('success');
        $this->actingAs($this->registrar)->post(route('registrar.sectioning.assign'), [
            'term_id' => $this->termId, 'section_id' => $sectionId, 'enrollment_ids' => [$second],
        ])->assertSessionHasErrors('placements');
        $this->assertNull(DB::table('student_enrollments')->where('id', $second)->value('section_id'));
    }
}
