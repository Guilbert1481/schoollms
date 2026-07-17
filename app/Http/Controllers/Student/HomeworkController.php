<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Student homework. Lists the published homework for the classes the student is
 * enrolled in (both tracks) and takes their submission — typed text and/or one
 * uploaded file, stored on the private disk. A student can only submit to a
 * class they belong to, and only download their own file.
 */
class HomeworkController extends Controller
{
    private const MIMES = 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv';

    public function index()
    {
        $student = $this->student();
        $classIds = $this->classIds($student);

        $homework = Homework::whereIn('class_id', $classIds)
            ->where('is_published', true)
            ->with(['submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderByDesc('id')
            ->get();

        // Subject/section labels for each homework's class.
        $classes = DB::table('classes as c')
            ->leftJoin('subjects as s', 's.id', '=', 'c.subject_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'c.section_id')
            ->whereIn('c.id', $homework->pluck('class_id'))
            ->get(['c.id', 's.name as subject', 'sec.name as section'])
            ->keyBy('id');

        return view('student.homework.index', [
            'homework' => $homework,
            'classes' => $classes,
        ]);
    }

    public function submit(Request $request, Homework $homework)
    {
        $student = $this->student();

        abort_unless($homework->is_published && $this->enrolledIn($student, (int) $homework->class_id), 403);

        $data = $request->validate([
            'body' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:'.self::MIMES, 'max:10240'],
        ]);

        $attrs = ['body' => $data['body'] ?? null, 'submitted_at' => now(), 'school_id' => $homework->school_id];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $attrs['file_path'] = $file->store("homework/{$homework->school_id}/{$homework->id}", 'local');
            $attrs['file_name'] = $file->getClientOriginalName();
        }

        HomeworkSubmission::updateOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            $attrs,
        );

        return back()->with('success', 'Your homework was submitted.');
    }

    public function downloadFile(HomeworkSubmission $submission)
    {
        $student = $this->student();
        abort_unless((int) $submission->student_id === (int) $student->id, 403);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name ?: 'submission');
    }

    /* ------------------------------------------------------------------ */

    private function student(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();
        abort_unless($student, 403);

        return $student;
    }

    /** Every class the student belongs to — higher-ed via subject enrolments, basic ed via their section. */
    private function classIds(Student $student): array
    {
        $higher = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->where('e.student_id', $student->id)
            ->pluck('ses.class_id');

        $sectionIds = DB::table('student_enrollments')
            ->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
            ->pluck('section_id')->filter();

        $basic = DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id');

        return $higher->merge($basic)->map(fn ($i) => (int) $i)->unique()->values()->all();
    }

    private function enrolledIn(Student $student, int $classId): bool
    {
        return in_array($classId, $this->classIds($student), true);
    }
}
