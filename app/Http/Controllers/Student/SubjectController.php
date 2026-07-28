<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function __construct(private StudentDashboardService $dashboard) {}

    /**
     * Display all subjects the authenticated student is enrolled in
     * for the currently active academic year + active term/semester.
     */
    public function index()
    {
        $user = Auth::user();

        // Resolve the student record linked to this user.
        $student = $user->student ?? \App\Models\Student::where('user_id', $user->id)->first();

        // One source of truth with the dashboard/clearance pages: resolves
        // across every active academic year (basic ed and higher ed each keep
        // their own active row) and only surfaces fully-enrolled students —
        // earlier lifecycle states (draft, submitted, assessed, billed,
        // partially_paid) stay hidden until the registrar finalises them.
        $enrollment = $this->dashboard->activeEnrollment($user);
        $enrollment?->loadMissing([
            'program:id,name,code',
            'section:id,name',
            'term:id,name,term,status,start_date,end_date',
        ]);

        // Header context: the enrolment's own year/term when resolved, else
        // whatever is currently active so the empty state can name it.
        $activeYear = $enrollment
            ? AcademicYear::where('school_id', $user->school_id)->find($enrollment->academic_year_id)
            : AcademicYear::where('school_id', $user->school_id)->where('is_active', true)->orderByDesc('id')->first();

        $activeTerm = $enrollment?->term
            ?? Term::where('school_id', $user->school_id)
                ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
                ->whereIn('status', ['active', 'upcoming'])
                ->orderByRaw("FIELD(status,'active','upcoming')")
                ->orderBy('start_date', 'desc')
                ->first();

        $subjects = collect();

        if ($enrollment) {
            // Higher ed: per-subject enrolment rows.
            $subjects = \App\Models\StudentEnrollmentSubject::with([
                'subject:id,name,code,description',
                'class:id,subject_id,section_id,code,room,schedule',
                'class.section:id,name',
            ])
                ->where('student_enrollment_id', $enrollment->id)
                ->where('status', \App\Models\StudentEnrollmentSubject::STATUS_ENROLLED)
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'code' => $row->subject->code ?? '—',
                        'name' => $row->subject->name ?? 'Untitled subject',
                        'description' => $row->subject->description,
                        'class_code' => $row->class->code ?? null,
                        'section' => $row->class->section->name ?? null,
                        'room' => $row->class->room ?? null,
                        'schedule' => $row->class->schedule ?? null,
                        'status' => $row->status,
                    ];
                })
                ->values();

            // Basic ed: subjects hang off the advisory section's classes, not
            // per-subject enrolment rows.
            if ($subjects->isEmpty() && $enrollment->section_id) {
                $sectionName = $enrollment->section->name ?? null;

                $subjects = DB::table('classes as c')
                    ->join('subjects as s', 's.id', '=', 'c.subject_id')
                    ->where('c.school_id', $user->school_id)
                    ->where('c.section_id', $enrollment->section_id)
                    ->orderBy('s.name')
                    ->get([
                        's.id', 's.code', 's.name', 's.description',
                        'c.code as class_code', 'c.room', 'c.schedule',
                    ])
                    ->map(fn ($row) => [
                        'id' => $row->id,
                        'code' => $row->code ?? '—',
                        'name' => $row->name,
                        'description' => $row->description,
                        'class_code' => $row->class_code,
                        'section' => $sectionName,
                        'room' => $row->room,
                        'schedule' => $row->schedule,
                        'status' => 'enrolled',
                    ])
                    ->values();
            }
        }

        return view('student.subjects.index', [
            'user' => $user,
            'student' => $student,
            'activeYear' => $activeYear,
            'activeTerm' => $activeTerm,
            'enrollment' => $enrollment,
            'subjects' => $subjects,
        ]);
    }
}
