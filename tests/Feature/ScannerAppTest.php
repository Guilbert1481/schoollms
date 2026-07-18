<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The standalone Scanner app (a SEPARATE installable PWA served under /scan).
 * Its home lists only the signed-in teacher's tests that have printed OMR
 * sheets, and its screens render in the scanner shell — which links
 * scanner.webmanifest, so the phone installs it as its own app rather than the
 * portal.
 */
class ScannerAppTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    }

    private function makeTest(string $title, ?User $owner = null): Test
    {
        $id = DB::table('tests')->insertGetId([
            'school_id' => $this->school->id,
            'teacher_id' => ($owner ?? $this->teacher)->id,
            'title' => $title,
            'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Test::findOrFail($id);
    }

    private function printSheet(Test $test): void
    {
        $studentId = DB::table('students')->insertGetId([
            'school_id' => $this->school->id, 'student_number' => 'S-'.uniqid(),
            'first_name' => 'Ana', 'last_name' => 'Cruz',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('omr_sheets')->insert([
            'school_id' => $this->school->id, 'test_id' => $test->id, 'student_id' => $studentId,
            'token' => 'tok-'.uniqid(), 'answer_key' => json_encode([]), 'item_count' => 10, 'max_score' => 10,
            'generated_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_scanner_home_requires_login(): void
    {
        $this->get('/scan')->assertRedirect();
    }

    public function test_home_lists_only_my_tests_that_have_printed_sheets(): void
    {
        $withSheets = $this->makeTest('Quarter Exam');
        $this->printSheet($withSheets);

        $this->makeTest('No Sheets Yet');                       // nothing printed → not scannable

        $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->printSheet($this->makeTest('Someone Elses Test', $other));

        $this->actingAs($this->teacher)->get('/scan')
            ->assertOk()
            ->assertSee('Quarter Exam')
            ->assertDontSee('No Sheets Yet')
            ->assertDontSee('Someone Elses Test');
    }

    public function test_scanner_screens_render_in_the_separate_app_shell(): void
    {
        $test = $this->makeTest('Quarter Exam');
        $this->printSheet($test);

        // Home: its own manifest → installs as a separate app, not the portal.
        $this->actingAs($this->teacher)->get('/scan')
            ->assertOk()
            ->assertSee('/scanner.webmanifest')
            ->assertDontSee('/manifest.webmanifest');

        // The camera screen is the SAME view the portal uses, in the app shell.
        $this->actingAs($this->teacher)->get('/scan/'.$test->id)
            ->assertOk()
            ->assertSee('/scanner.webmanifest');
    }

    public function test_the_portal_scan_page_keeps_the_portal_shell(): void
    {
        $test = $this->makeTest('Quarter Exam');
        $this->printSheet($test);

        $this->actingAs($this->teacher)
            ->get(route('teacher.tests.omr.scan-camera', $test->id))
            ->assertOk()
            ->assertSee('/manifest.webmanifest')
            ->assertDontSee('/scanner.webmanifest');
    }
}
