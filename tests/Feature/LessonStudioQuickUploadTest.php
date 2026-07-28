<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The lesson list (Level 2) lets a content-adder upload a resource straight to a
 * lesson — no drilling in, no competency required — and `stay` keeps them on the
 * list so several lessons can be filled in a row.
 */
class LessonStudioQuickUploadTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: User, 2: int, 3: int, 4: int} */
    private function scaffold(): array
    {
        $school = School::factory()->create();
        $ca = User::factory()->create(['school_id' => $school->id, 'role' => 'course_architect']);

        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $school->id, 'code' => 'NCM-112', 'name' => 'Med-Surg',
            'is_basic_ed' => 0, 'scope' => 'academic', 'category' => 'prof_ed', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $topicId = DB::table('topics')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'name' => 'Cardiac Fundamentals',
            'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $lessonId = DB::table('lessons')->insertGetId([
            'school_id' => $school->id, 'subject_id' => $subjectId, 'topic_id' => $topicId,
            'name' => 'Blood Flow Through the Heart', 'sort_order' => 1, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$school, $ca, $subjectId, $topicId, $lessonId];
    }

    public function test_quick_upload_creates_resource_and_stays_on_the_lesson_list(): void
    {
        Storage::fake('public');
        [$school, $ca, $s, $t, $l] = $this->scaffold();

        $resp = $this->actingAs($ca)->post(route('course-architect.lesson-studio.store'), [
            'subject_id' => $s, 'topic_id' => $t, 'lesson_id' => $l, 'stay' => 1,
            'file' => UploadedFile::fake()->create('lesson.pptx', 20, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
        ]);

        $resp->assertRedirect(route('course-architect.lesson-studio.topic', [$s, $t]));
        $this->assertDatabaseHas('lesson_resources', [
            'school_id' => $school->id, 'lesson_id' => $l, 'competency_id' => null, 'file_type' => 'ppt',
        ]);
    }

    public function test_default_upload_still_lands_on_the_lesson(): void
    {
        Storage::fake('public');
        [$school, $ca, $s, $t, $l] = $this->scaffold();

        $resp = $this->actingAs($ca)->post(route('course-architect.lesson-studio.store'), [
            'subject_id' => $s, 'topic_id' => $t, 'lesson_id' => $l,
            'file' => UploadedFile::fake()->create('lesson.pdf', 20, 'application/pdf'),
        ]);

        $resp->assertRedirect(route('course-architect.lesson-studio.lesson', [$s, $t, $l]));
    }

    public function test_lesson_list_offers_the_upload_action_to_a_course_architect(): void
    {
        [$school, $ca, $s, $t, $l] = $this->scaffold();

        $resp = $this->actingAs($ca)->get(route('course-architect.lesson-studio.topic', [$s, $t]));

        $resp->assertOk();
        $actions = collect($resp->viewData('rowActions'));
        $this->assertTrue(
            $actions->contains(fn ($a) => ($a['name'] ?? null) === 'upload'),
            'The lesson list must offer an Upload row action to a course architect.'
        );
        $this->assertTrue($resp->viewData('canUploadContent'));
    }
}
