<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar's enrollment-validation queue.
 *
 * Workflow:
 *  - Admission Manager opens the enrolment window and publishes sections.
 *  - Students submit enrolment (status = 'submitted').
 *  - Registrar reviews here -> approves (status = 'validated') or rejects.
 *  - Approved enrolments then move on to billing/payment gates.
 */
class EnrollmentValidationController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? null;

        $status = $request->string('status', 'submitted')->toString();
        $allowed = ['submitted', 'validated', 'rejected', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'submitted';
        }

        $termId = $request->integer('term_id') ?: optional(
            DB::table('terms')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->first()
        )->id;

        $terms = DB::table('terms')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $enrollments = StudentEnrollment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['student:id,first_name,last_name,student_number', 'program:id,name,code'])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('registrar.enrollments.index', compact(
            'enrollments', 'terms', 'termId', 'status'
        ));
    }

    public function show(StudentEnrollment $enrollment)
    {
        $enrollment->load(['student', 'program', 'term']);

        return view('registrar.enrollments.show', compact('enrollment'));
    }

    public function validateEnrollment(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate(['remarks' => 'nullable|string|max:1000']);

        $enrollment->update([
            'status' => 'validated',
            'approval_level' => 'registrar',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks') ?: $enrollment->remarks,
        ]);

        return back()->with('success', 'Enrollment validated.');
    }

    public function reject(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);

        $enrollment->update([
            'status' => 'rejected',
            'approval_level' => 'registrar',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('success', 'Enrollment rejected.');
    }
}
