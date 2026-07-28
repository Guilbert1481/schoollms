<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\School;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Course Architect / Subject Coordinator Lesson Studio — per-row actions.
 *
 * Subjects are created by the Principal and stay read-only here, so the Subjects
 * level must render WITHOUT an actions column while Topics and Lessons get
 * View / Rename / Delete. The rename endpoint is school-scoped and refuses to
 * touch a Subject at all.
 */
class LessonStudioFolderActionsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $coordinator;

    private Subject $subject;

    private Topic $topic;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->coordinator = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'subject_coordinator',
        ]);

        $this->subject = $this->makeSubject('English', 'ENG-1', basicEd: true);
        $this->topic = Topic::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'name' => 'Present Simple',
        ]);
        $this->lesson = Lesson::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'name' => 'Forming the tense',
        ]);
    }

    /**
     * `is_basic_ed` is not mass-assignable on Subject (and the column defaults to
     * false), so it has to be set directly — otherwise every fixture would look
     * like a college subject and trip the outline fence.
     */
    private function makeSubject(string $name, string $code, bool $basicEd, ?int $schoolId = null): Subject
    {
        $subject = Subject::create([
            'school_id' => $schoolId ?? $this->school->id,
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);

        $subject->forceFill(['is_basic_ed' => $basicEd])->save();

        return $subject;
    }

    public function test_subjects_level_renders_without_row_actions(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('course-architect.lesson-studio.index'))
            ->assertOk()
            ->assertViewHas('folderType', null)
            // No handler wired up means no actions column was rendered.
            ->assertDontSee('lsFolderEdit')
            ->assertDontSee('lsFolderDelete');
    }

    public function test_topics_level_renders_view_rename_delete_actions(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('course-architect.lesson-studio.subject', $this->subject->id))
            ->assertOk()
            ->assertViewHas('folderType', 'topic')
            ->assertSee('lsFolderOpen')
            ->assertSee('lsFolderEdit')
            ->assertSee('lsFolderDelete')
            ->assertSee('Rename Topic');
    }

    public function test_lessons_level_renders_view_rename_delete_actions(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('course-architect.lesson-studio.topic', [$this->subject->id, $this->topic->id]))
            ->assertOk()
            ->assertViewHas('folderType', 'lesson')
            ->assertSee('lsFolderEdit')
            ->assertSee('Rename Lesson');
    }

    public function test_coordinator_can_rename_a_topic(): void
    {
        $this->actingAs($this->coordinator)
            ->from(route('course-architect.lesson-studio.subject', $this->subject->id))
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $this->topic->id]), [
                'name' => '  Past Simple  ',
            ])
            ->assertRedirect();

        // Trimmed on the way in.
        $this->assertSame('Past Simple', $this->topic->fresh()->name);
    }

    public function test_coordinator_can_rename_a_lesson(): void
    {
        $this->actingAs($this->coordinator)
            ->put(route('course-architect.lesson-studio.folder.update', ['lesson', $this->lesson->id]), [
                'name' => 'Irregular verbs',
            ]);

        $this->assertSame('Irregular verbs', $this->lesson->fresh()->name);
    }

    public function test_rename_rejects_a_blank_name(): void
    {
        $this->actingAs($this->coordinator)
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $this->topic->id]), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame('Present Simple', $this->topic->fresh()->name);
    }

    /** Subjects are Principal-owned: the route never accepts `subject` as a type. */
    public function test_subject_cannot_be_renamed_through_this_endpoint(): void
    {
        $this->actingAs($this->coordinator)
            ->put('/course-architect/lesson-studio/folder/subject/'.$this->subject->id, [
                'name' => 'Hijacked',
            ])
            ->assertNotFound();

        $this->assertSame('English', $this->subject->fresh()->name);
    }

    public function test_another_schools_topic_cannot_be_renamed(): void
    {
        $otherSchool = School::factory()->create();
        // Basic-ed on purpose, so the refusal can only come from school scoping.
        $otherSubject = $this->makeSubject('Science', 'SCI-1', basicEd: true, schoolId: $otherSchool->id);
        $otherTopic = Topic::create([
            'school_id' => $otherSchool->id,
            'subject_id' => $otherSubject->id,
            'name' => 'Photosynthesis',
        ]);

        $this->actingAs($this->coordinator)
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $otherTopic->id]), [
                'name' => 'Hijacked',
            ])
            ->assertNotFound();

        $this->assertSame('Photosynthesis', $otherTopic->fresh()->name);
    }

    public function test_a_student_cannot_rename_a_topic(): void
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $this->topic->id]), [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertSame('Present Simple', $this->topic->fresh()->name);
    }

    /* ===================================================================
       Structure vs content split.

       Higher ed: the Program Head owns the Topic/Lesson/Competency outline,
       so a course_architect only adds CONTENT. Basic ed: the coordinator
       owns both — but only on basic-ed subjects.
       =================================================================== */

    private function architect(): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'course_architect',
        ]);
    }

    /** A college subject: the Program Head's territory, off-limits to the outline routes. */
    private function higherEdSubject(): Subject
    {
        return $this->makeSubject('Nursing Practice', 'NCM-101', basicEd: false);
    }

    public function test_course_architect_cannot_create_folders(): void
    {
        $this->actingAs($this->architect())
            ->post(route('course-architect.lesson-studio.folder.store'), [
                'subject_id' => $this->subject->id,
                'names' => ['Smuggled Topic'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('topics', ['name' => 'Smuggled Topic']);
    }

    public function test_course_architect_cannot_rename_or_delete_folders(): void
    {
        $architect = $this->architect();

        $this->actingAs($architect)
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $this->topic->id]), [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->actingAs($architect)
            ->delete(route('course-architect.lesson-studio.folder.destroy', ['topic', $this->topic->id]))
            ->assertForbidden();

        $this->assertSame('Present Simple', $this->topic->fresh()->name);
        $this->assertDatabaseHas('topics', ['id' => $this->topic->id]);
    }

    public function test_course_architect_cannot_reorder_folders(): void
    {
        $this->actingAs($this->architect())
            ->post(route('course-architect.lesson-studio.folder.reorder'), [
                'type' => 'topic',
                'ids' => [$this->topic->id],
            ])
            ->assertForbidden();
    }

    /** Content is still shared — the architect keeps the half of the studio it owns. */
    public function test_course_architect_can_still_browse_and_add_content(): void
    {
        $architect = $this->architect();

        $this->actingAs($architect)
            ->get(route('course-architect.lesson-studio.lesson', [
                $this->subject->id, $this->topic->id, $this->lesson->id,
            ]))
            ->assertOk();

        $this->actingAs($architect)
            ->post(route('course-architect.lesson-studio.store'), [
                'subject_id' => $this->subject->id,
                'topic_id' => $this->topic->id,
                'lesson_id' => $this->lesson->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_resources', ['lesson_id' => $this->lesson->id]);
    }

    public function test_course_architect_sees_no_outline_controls(): void
    {
        $this->actingAs($this->architect())
            ->get(route('course-architect.lesson-studio.subject', $this->subject->id))
            ->assertOk()
            ->assertViewHas('canManageFolders', false)
            ->assertViewHas('folderType', null)
            // No actions column, no bulk-add button, no parent-pick checkbox.
            ->assertDontSee('lsFolderEdit')
            ->assertDontSee('lsOpenBulkAdd()')
            ->assertDontSee('ls-pick');
    }

    public function test_coordinator_still_sees_outline_controls(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('course-architect.lesson-studio.subject', $this->subject->id))
            ->assertOk()
            ->assertViewHas('canManageFolders', true)
            ->assertSee('lsOpenBulkAdd()', false)
            ->assertSee('ls-pick', false);
    }

    /* ── The basic-ed fence: route gating alone would not stop these ── */

    public function test_coordinator_cannot_create_folders_under_a_higher_ed_subject(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('course-architect.lesson-studio.folder.store'), [
                'subject_id' => $this->higherEdSubject()->id,
                'names' => ['Smuggled Topic'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('topics', ['name' => 'Smuggled Topic']);
    }

    public function test_coordinator_cannot_rename_a_higher_ed_topic(): void
    {
        $topic = Topic::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->higherEdSubject()->id,
            'name' => 'Vital Signs',
        ]);

        $this->actingAs($this->coordinator)
            ->put(route('course-architect.lesson-studio.folder.update', ['topic', $topic->id]), [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertSame('Vital Signs', $topic->fresh()->name);
    }

    public function test_coordinator_cannot_delete_a_higher_ed_topic(): void
    {
        $topic = Topic::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->higherEdSubject()->id,
            'name' => 'Vital Signs',
        ]);

        $this->actingAs($this->coordinator)
            ->delete(route('course-architect.lesson-studio.folder.destroy', ['topic', $topic->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
    }

    public function test_coordinator_cannot_reorder_higher_ed_topics(): void
    {
        $subject = $this->higherEdSubject();
        $topic = Topic::create([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'name' => 'Vital Signs',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->coordinator)
            ->post(route('course-architect.lesson-studio.folder.reorder'), [
                'type' => 'topic',
                'ids' => [$topic->id],
            ])
            ->assertForbidden();
    }

    /** Browsing a higher-ed subject is fine — it just renders read-only. */
    public function test_coordinator_browsing_a_higher_ed_subject_gets_no_outline_controls(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('course-architect.lesson-studio.subject', $this->higherEdSubject()->id))
            ->assertOk()
            ->assertViewHas('canManageFolders', false)
            ->assertViewHas('folderType', null);
    }
}
