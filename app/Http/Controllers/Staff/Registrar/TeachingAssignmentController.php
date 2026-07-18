<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registrar → Teaching Assignments. Assigns a teacher to teach a subject to a
 * section — the class row that is the source of truth for the teacher's
 * gradebook and per-subject attendance.
 *
 * A class is unique on (subject, term, section), so this UPSERTS that row and
 * sets its teacher: it composes with the Scheduler (which sets the schedule on
 * the same row) instead of creating duplicates. The subject list is scoped to
 * what the teacher is qualified to teach (teacher_subjects, bridged through the
 * teacher_profile), and that capability is enforced on save.
 */
class TeachingAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();

        $teachers = DB::table('users')
            ->where('school_id', $schoolId)->where('role', 'teacher')
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => trim("{$u->last_name}, {$u->first_name}")])
            ->values();

        $subjects = DB::table('subjects')->where('school_id', $schoolId)->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'code' => $s->code, 'name' => $s->name])
            ->values();

        $sections = DB::table('sections')->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        // userId => [subjectId, ...] a teacher is qualified for.
        $subjectsByTeacher = [];
        foreach (DB::table('teacher_subjects as ts')
            ->join('teacher_profiles as tp', 'tp.id', '=', 'ts.teacher_id')
            ->join('subjects as s', 's.id', '=', 'ts.subject_id')
            ->where('s.school_id', $schoolId)
            ->get(['tp.user_id', 'ts.subject_id']) as $r) {
            $subjectsByTeacher[(int) $r->user_id][] = (int) $r->subject_id;
        }

        $assignments = DB::table('classes as c')
            ->join('subjects as s', 's.id', '=', 'c.subject_id')
            ->join('sections as sec', 'sec.id', '=', 'c.section_id')
            ->join('users as u', 'u.id', '=', 'c.teacher_id')
            ->leftJoin('terms as t', 't.id', '=', 'c.term_id')
            ->where('c.school_id', $schoolId)
            ->orderBy('u.last_name')->orderBy('s.name')
            ->get([
                'c.id',
                's.name as subject',
                'sec.name as section',
                DB::raw("TRIM(CONCAT(COALESCE(u.last_name,''),', ',COALESCE(u.first_name,''))) as teacher"),
                't.term as term',
                't.academic_year as ay',
            ]);

        return view('registrar.teaching-assignments.index', compact('teachers', 'subjects', 'sections', 'subjectsByTeacher', 'assignments'));
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'teacher_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
        ]);

        // Everything must belong to this school (tenant guard on crafted ids).
        $this->assertBelongs('users', $data['teacher_id'], $schoolId, ['role' => 'teacher']);
        $this->assertBelongs('subjects', $data['subject_id'], $schoolId);
        $this->assertBelongs('sections', $data['section_id'], $schoolId);

        // A section already belongs to a term — the class inherits it.
        $termId = (int) DB::table('sections')->where('id', $data['section_id'])->value('term_id');
        abort_unless($termId, 422);

        // Capability: if the teacher has qualified subjects, this must be one.
        $qualified = DB::table('teacher_subjects as ts')
            ->join('teacher_profiles as tp', 'tp.id', '=', 'ts.teacher_id')
            ->where('tp.user_id', $data['teacher_id'])
            ->pluck('ts.subject_id')->map(fn ($i) => (int) $i);

        if ($qualified->isNotEmpty() && ! $qualified->contains((int) $data['subject_id'])) {
            throw ValidationException::withMessages(['subject_id' => 'That teacher is not assigned to teach this subject.']);
        }

        // Upsert the class on its unique key; only set/replace the teacher.
        $match = ['school_id' => $schoolId, 'subject_id' => $data['subject_id'], 'term_id' => $termId, 'section_id' => $data['section_id']];
        $existing = DB::table('classes')->where($match)->first(['id']);

        if ($existing) {
            DB::table('classes')->where('id', $existing->id)->update(['teacher_id' => $data['teacher_id'], 'updated_at' => now()]);
        } else {
            DB::table('classes')->insert($match + [
                'teacher_id' => $data['teacher_id'],
                'code' => $this->code($data['subject_id'], $data['section_id']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Teaching assignment saved.');
    }

    public function destroy(Request $request, int $class)
    {
        $schoolId = $this->schoolId();

        abort_unless(DB::table('classes')->where('id', $class)->where('school_id', $schoolId)->exists(), 404);

        // Never cascade-delete recorded grades or attendance.
        $hasData = DB::table('component_scores')->where('class_id', $class)->exists()
            || DB::table('attendance_records')->where('class_id', $class)->exists();

        if ($hasData) {
            return back()->with('error', 'This class already has grades or attendance recorded and cannot be removed.');
        }

        DB::table('classes')->where('id', $class)->delete();

        return back()->with('success', 'Teaching assignment removed.');
    }

    /* ------------------------------------------------------------------ */

    private function schoolId(): int
    {
        $id = (int) auth()->user()->school_id;
        abort_unless($id, 404);

        return $id;
    }

    /** @param array<string, mixed> $extra */
    private function assertBelongs(string $table, int $id, int $schoolId, array $extra = []): void
    {
        $query = DB::table($table)->where('id', $id)->where('school_id', $schoolId);
        foreach ($extra as $col => $val) {
            $query->where($col, $val);
        }

        abort_unless($query->exists(), 422);
    }

    private function code(int $subjectId, int $sectionId): string
    {
        $subject = DB::table('subjects')->where('id', $subjectId)->value('code') ?: 'SUBJ';
        $section = DB::table('sections')->where('id', $sectionId)->value('name') ?: 'SEC';

        return substr("{$subject}-{$section}", 0, 64);
    }
}
