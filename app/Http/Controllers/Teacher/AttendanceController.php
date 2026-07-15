<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Attendance\AttendanceIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Teacher-facing manual attendance capture (Phase 1). A teacher either takes
 * DAILY attendance for an advisory section they adviser, or SESSION attendance
 * for a class they teach. All writes go through AttendanceIngestionService, the
 * same funnel QR and device capture will use later.
 */
class AttendanceController extends Controller
{
    /** Enrolment statuses that put a student on a live roster. */
    private const ROSTER_STATUSES = [
        StudentEnrollment::STATUS_ENROLLED,
        StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
    ];

    public function index(Request $request)
    {
        $userId = Auth::id();

        // The two things this teacher can take attendance for.
        $sections = Section::where('adviser_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'year_level']);

        // subject/section are lazy-loaded in the view (a teacher has few classes).
        $classes = ClassModel::where('teacher_id', $userId)
            ->orderBy('code')
            ->get();

        $date = $this->resolveDate($request->query('date'));
        $type = $request->query('type');
        $roster = collect();
        $context = null;

        if ($type === AttendanceRecord::SCOPE_DAILY && $request->filled('section_id')) {
            $section = $sections->firstWhere('id', (int) $request->query('section_id'));
            if ($section) {
                $context = ['type' => 'daily', 'section' => $section];
                $roster = $this->dailyRoster($section, $date);
            }
        } elseif ($type === AttendanceRecord::SCOPE_SESSION && $request->filled('class_id')) {
            $class = $classes->firstWhere('id', (int) $request->query('class_id'));
            if ($class) {
                $context = ['type' => 'session', 'class' => $class];
                $roster = $this->sessionRoster($class, $date);
            }
        }

        return view('teacher.attendance.index', [
            'sections' => $sections,
            'classes' => $classes,
            'date' => $date,
            'context' => $context,
            'roster' => $roster,
            'statuses' => AttendanceRecord::STATUSES,
        ]);
    }

    public function store(Request $request, AttendanceIngestionService $ingestion)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([AttendanceRecord::SCOPE_DAILY, AttendanceRecord::SCOPE_SESSION])],
            'date' => ['required', 'date'],
            'section_id' => ['required_if:type,daily', 'nullable', 'integer'],
            'class_id' => ['required_if:type,session', 'nullable', 'integer'],
            'marks' => ['required', 'array'],
            'marks.*.student_id' => ['required', 'integer'],
            'marks.*.status' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'marks.*.time_in' => ['nullable', 'date_format:H:i'],
            'marks.*.time_out' => ['nullable', 'date_format:H:i'],
        ]);

        $userId = Auth::id();
        $date = $this->resolveDate($validated['date']);

        // Re-establish the context from ownership, never from the posted ids
        // alone — a teacher may only mark rosters they adviser or teach.
        if ($validated['type'] === AttendanceRecord::SCOPE_DAILY) {
            $section = Section::where('adviser_id', $userId)->findOrFail($validated['section_id']);
            $roster = $this->dailyRoster($section, $date);
            $base = [
                'scope' => AttendanceRecord::SCOPE_DAILY,
                'section_id' => $section->id,
                'term_id' => $section->term_id,
            ];
        } else {
            $class = ClassModel::where('teacher_id', $userId)->findOrFail($validated['class_id']);
            $roster = $this->sessionRoster($class, $date);
            $base = [
                'scope' => AttendanceRecord::SCOPE_SESSION,
                'class_id' => $class->id,
                'section_id' => $class->section_id,
                'term_id' => $class->term_id,
            ];
        }

        $rosterStudentIds = $roster->pluck('student_id');
        $ayByStudent = $roster->pluck('academic_year_id', 'student_id');

        $saved = 0;
        foreach ($validated['marks'] as $mark) {
            $studentId = (int) $mark['student_id'];

            // Ignore any student id not actually on this roster (tampering guard).
            if (! $rosterStudentIds->contains($studentId)) {
                continue;
            }

            $ingestion->record($base + [
                'student_id' => $studentId,
                'attendance_date' => $date,
                'academic_year_id' => $ayByStudent[$studentId] ?? null,
                'time_in' => $mark['time_in'] ?? null,
                'time_out' => $mark['time_out'] ?? null,
                'status' => $mark['status'],
                'method' => AttendanceRecord::METHOD_MANUAL,
                'recorded_by' => $userId,
            ]);
            $saved++;
        }

        return redirect()
            ->route('teacher.attendance.index', array_filter([
                'type' => $validated['type'],
                'section_id' => $validated['section_id'] ?? null,
                'class_id' => $validated['class_id'] ?? null,
                'date' => $date,
            ]))
            ->with('status', "Attendance saved for {$saved} student(s).");
    }

    /* ------------------------------------------------------------------ */

    /** Basic-ed homeroom roster: students enrolled in this advisory section. */
    private function dailyRoster(Section $section, string $date)
    {
        // Join through the enrolment (Student::query keeps the tenant scope) so
        // each row is a Student carrying its enrolment's academic year.
        return Student::query()
            ->join('student_enrollments as se', 'se.student_id', '=', 'students.id')
            ->where('se.section_id', $section->id)
            ->whereIn('se.status', self::ROSTER_STATUSES)
            ->get([
                'students.id', 'students.first_name', 'students.middle_name',
                'students.last_name', 'students.student_number', 'se.academic_year_id',
            ])
            ->map(fn ($student) => $this->rosterRow(
                $student,
                AttendanceRecord::SCOPE_DAILY,
                ['section_id' => $section->id, 'class_id' => null],
                $date,
                ['academic_year_id' => $student->getAttribute('academic_year_id')]
            ))
            ->sortBy('name')
            ->values();
    }

    /** Non-basic roster: students enrolled in this class. */
    private function sessionRoster(ClassModel $class, string $date)
    {
        return $class->students()
            ->get(['students.id', 'first_name', 'middle_name', 'last_name', 'student_number'])
            ->map(fn ($student) => $this->rosterRow(
                $student,
                AttendanceRecord::SCOPE_SESSION,
                ['class_id' => $class->id, 'section_id' => $class->section_id],
                $date
            ))
            ->sortBy('name')
            ->values();
    }

    /**
     * Build one roster row: the student plus any attendance already recorded for
     * this context/date, so the form shows what's saved.
     *
     * @param  array{section_id?: int|null, class_id?: int|null}  $ctx
     * @param  array<string, mixed>  $extra
     */
    private function rosterRow($student, string $scope, array $ctx, string $date, array $extra = []): array
    {
        $existing = AttendanceRecord::where('student_id', $student->id)
            ->where('scope', $scope)
            ->whereDate('attendance_date', $date)
            ->when($scope === AttendanceRecord::SCOPE_DAILY,
                fn ($q) => $q->where('section_id', $ctx['section_id']),
                fn ($q) => $q->where('class_id', $ctx['class_id']))
            ->first();

        return array_merge([
            'student_id' => $student->id,
            'name' => trim("{$student->last_name}, {$student->first_name}"),
            'number' => $student->student_number,
            'status' => $existing->status ?? null,
            'time_in' => $existing?->time_in,
            'time_out' => $existing?->time_out,
        ], $extra);
    }

    private function resolveDate(?string $raw): string
    {
        try {
            return $raw ? \Illuminate\Support\Carbon::parse($raw)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
