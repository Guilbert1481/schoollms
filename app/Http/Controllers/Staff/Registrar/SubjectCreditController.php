<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectCreditEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar's subject credit evaluation tool.
 *
 * Used for transferees, irregulars, shifters, and returnees to credit
 * (or reject credit for) curriculum subjects based on prior coursework.
 */
class SubjectCreditController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? null;

        $status = $request->string('status', 'pending')->toString();
        $allowed = ['pending', 'credited', 'rejected', 'conditional', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $reason = $request->string('reason', 'all')->toString();

        $evaluations = SubjectCreditEvaluation::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($reason !== 'all', fn ($q) => $q->where('reason', $reason))
            ->with([
                'student:id,first_name,last_name,student_number',
                'subject:id,code,title,units',
                'evaluator:id,email',
            ])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $students = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['transferee', 'irregular', 'shifter', 'returnee', 'active'])
            ->orderBy('last_name')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'student_number', 'status']);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('code')
            ->get(['id', 'code', 'title', 'units']);

        return view('registrar.subject-credits.index', compact(
            'evaluations', 'students', 'subjects', 'status', 'reason'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'reason' => 'required|in:transferee,irregular,shifter,returnee,other',
            'source_subject_code' => 'nullable|string|max:50',
            'source_subject_title' => 'nullable|string|max:255',
            'source_school' => 'nullable|string|max:255',
            'source_grade' => 'nullable|string|max:20',
            'source_units' => 'nullable|numeric|min:0|max:99',
            'credited_units' => 'nullable|numeric|min:0|max:99',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $student = DB::table('students')->where('id', $data['student_id'])->first();

        SubjectCreditEvaluation::create($data + [
            'school_id' => $student->school_id ?? auth()->user()->school_id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Credit evaluation request created.');
    }

    public function decide(Request $request, SubjectCreditEvaluation $evaluation)
    {
        $data = $request->validate([
            'status' => 'required|in:credited,rejected,conditional',
            'credited_units' => 'nullable|numeric|min:0|max:99',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $evaluation->update($data + [
            'evaluated_by' => auth()->id(),
            'evaluated_at' => now(),
        ]);

        return back()->with('success', 'Evaluation updated.');
    }

    public function destroy(SubjectCreditEvaluation $evaluation)
    {
        $evaluation->delete();

        return back()->with('success', 'Evaluation removed.');
    }
}
