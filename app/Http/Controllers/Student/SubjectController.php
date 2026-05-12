<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Display all subjects the authenticated student is enrolled in
     * for the currently active academic year + active term/semester.
     */
    public function index()
    {
        $user = Auth::user();

        // Resolve the student record linked to this user.
        $student = $user->student ?? \App\Models\Student::where('user_id', $user->id)->first();

        $activeYear = AcademicYear::where('school_id', $user->school_id)
            ->where('is_active', true)
            ->first();

        // Pick whichever term is currently relevant to the student.
        // "active" = classes in session; "upcoming" = enrolment is open but
        // classes have not started yet. Both should reveal the My Subjects page
        // so newly-enrolled students can preview their schedule.
        $activeTerm = Term::where('school_id', $user->school_id)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->whereIn('status', ['active', 'upcoming'])
            ->orderByRaw("FIELD(status,'active','upcoming')")
            ->orderBy('start_date', 'desc')
            ->first();

        $enrollment = null;
        $subjects   = collect();

        if ($student && $activeYear) {
            // Only show subjects when the student is fully enrolled.
            // Earlier lifecycle states (draft, submitted, assessed, billed,
            // partially_paid) mean enrolment is still in progress, so the
            // subjects list stays hidden until the registrar finalises it.
            $enrollment = StudentEnrollment::with([
                    'program:id,name,code',
                    'section:id,name',
                    'term:id,name,term,status,start_date,end_date',
                ])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('status', StudentEnrollment::STATUS_ENROLLED)
                ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
                ->latest('id')
                ->first();

            if (! $enrollment) {
                $enrollment = StudentEnrollment::with([
                        'program:id,name,code',
                        'section:id,name',
                        'term:id,name,term,status,start_date,end_date',
                    ])
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('status', StudentEnrollment::STATUS_ENROLLED)
                    ->latest('id')
                    ->first();
            }

            // If we resolved an enrolment, prefer its term for the header.
            if ($enrollment && $enrollment->term) {
                $activeTerm = $enrollment->term;
            }

            if ($enrollment) {
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
                            'id'          => $row->id,
                            'code'        => $row->subject->code ?? '—',
                            'name'        => $row->subject->name ?? 'Untitled subject',
                            'description' => $row->subject->description,
                            'class_code'  => $row->class->code ?? null,
                            'section'     => $row->class->section->name ?? null,
                            'room'        => $row->class->room ?? null,
                            'schedule'    => $row->class->schedule ?? null,
                            'status'      => $row->status,
                        ];
                    })
                    ->values();
            }
        }

        return view('student.subjects.index', [
            'user'       => $user,
            'student'    => $student,
            'activeYear' => $activeYear,
            'activeTerm' => $activeTerm,
            'enrollment' => $enrollment,
            'subjects'   => $subjects,
        ]);
    }
}
