<?php

namespace Tests\Feature;

use App\Models\EnrollmentSetting;
use App\Models\School;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The public enrolment wizard is reached by guessable numeric term ids
 * (/apply/{term}), so EnsureEnrollmentOpen must FAIL CLOSED: only a term whose
 * admission session is currently Active may show the form. Regression for the
 * 2026-07-28 finding where a term with no enrollment_settings row at all
 * skipped the gate entirely and accepted applications.
 */
class EnrollmentOpenGateTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTerm(): Term
    {
        return Term::create([
            'school_id' => $this->school->id,
            'name' => '1st Semester',
            'academic_year' => '2026-2027',
            'enrollment_type' => 'regular',
            'term' => '1st',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 'active',
        ]);
    }

    private function makeSession(Term $term, string $startDate, string $endDate): EnrollmentSetting
    {
        return EnrollmentSetting::create([
            'name' => 'Admission '.$term->id,
            'academic_year_id' => $this->academicYearId,
            'term_id' => $term->id,
            'is_open' => true,
            'is_active' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function test_term_without_a_configured_session_fails_closed(): void
    {
        $term = $this->makeTerm();

        $response = $this->get(route('public.apply.show', $term->id));

        $response->assertOk();
        $response->assertSee('Enrollment Closed');
        $response->assertSee('1st Semester');
        $response->assertDontSee('STEP 1 OF 9');
    }

    public function test_term_with_an_active_session_shows_the_form(): void
    {
        $term = $this->makeTerm();
        $this->makeSession($term, now()->subWeek()->toDateString(), now()->addWeek()->toDateString());

        $response = $this->get(route('public.apply.show', $term->id));

        $response->assertOk();
        $response->assertSee('STEP 1 OF 9');
        $response->assertDontSee('Enrollment Closed');
    }

    public function test_term_with_an_expired_session_shows_the_closed_notice(): void
    {
        $term = $this->makeTerm();
        $this->makeSession($term, now()->subMonths(2)->toDateString(), now()->subWeek()->toDateString());

        $response = $this->get(route('public.apply.show', $term->id));

        $response->assertOk();
        $response->assertSee('Enrollment Closed');
        $response->assertDontSee('STEP 1 OF 9');
    }

    public function test_term_with_an_upcoming_session_shows_the_not_yet_open_notice(): void
    {
        $term = $this->makeTerm();
        $this->makeSession($term, now()->addWeek()->toDateString(), now()->addMonth()->toDateString());

        $response = $this->get(route('public.apply.show', $term->id));

        $response->assertOk();
        $response->assertSee('Enrollment Not Yet Open');
        $response->assertDontSee('STEP 1 OF 9');
    }

    public function test_qr_landing_also_fails_closed_for_unconfigured_terms(): void
    {
        $term = $this->makeTerm();

        $response = $this->get(route('public.apply.qr', $term->id));

        $response->assertOk();
        $response->assertSee('Enrollment Closed');
    }
}
