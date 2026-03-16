<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Enrollment;
use App\Models\EnrollmentDraft;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Show the enrollment form (Step 1).
     */
    public function show($semesterId)
    {
        $semester = Semester::findOrFail($semesterId);

        $student = null;

        if (Auth::check() && Auth::user()->student) {
            $student = Auth::user()->student;
        }

        return view('public.enrollment_form', compact('semester', 'student'));
    }

    /**
     * Save Step 1 (Personal Info) and move to next step.
     */
    public function store(Request $request, $semesterId)
    {
        if (!auth()->check()) {
            return $request->ajax()
                ? response()->json(['error' => 'Session expired.'], 401)
                : redirect()->route('login');
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'gender'       => 'required|string',
            'dob'          => 'required|date',
            'nationality'  => 'required|string',
            'civil_status' => 'required|string',

            'middle_name'          => 'nullable|string|max:255',
            'preferred_name'       => 'nullable|string|max:255',
            'sexual_orientation'   => 'nullable|string',
            'government_id_type'   => 'nullable|string',
            'government_id_number' => 'nullable|string',
        ]);

        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            $student = Student::create([
                'user_id'    => $user->id,
                'school_id'  => $request->school_id ?? 1,
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $user->email,
            ]);
        }

        if ($request->hasFile('student_photo')) {
            $student->photo_path = $request->file('student_photo')
                ->store('profile_photos', 'public');
        }

        if ($request->hasFile('id_file')) {
            $student->photo_id = $request->file('id_file')
                ->store('id_documents', 'public');
        }

        $student->update([
            'first_name'           => $validated['first_name'],
            'middle_name'          => $request->middle_name,
            'last_name'            => $validated['last_name'],
            'preferred_name'       => $request->preferred_name,
            'gender'               => $validated['gender'],
            'sexual_orientation'   => $request->sexual_orientation,
            'date_of_birth'        => $validated['dob'],
            'nationality'          => $validated['nationality'],
            'civil_status'         => $validated['civil_status'],
            'government_id_type'   => $request->government_id_type,
            'government_id_number' => $request->government_id_number,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success'  => true,
                'next_url' => route('public.apply.step2', $semesterId)
            ]);
        }

        return redirect()->route('public.apply.step2', $semesterId);
    }

    /**
     * Save Draft
     */
    public function saveDraft(Request $request, $semesterId)
    {
        if (!Auth::check() || !Auth::user()->student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $student = Auth::user()->student;

        EnrollmentDraft::updateOrCreate(
            [
                'student_id'  => $student->id,
                'semester_id' => $semesterId,
            ],
            [
                'data' => json_encode($request->all())
            ]
        );

        return response()->json(['status' => 'saved']);
    }

    /**
     * Show Step 2
     */
    public function showStep2($semesterId)
    {
        $semester = Semester::findOrFail($semesterId);
        $student  = auth()->user()->student;

        return view('public.contact_details', compact('semester', 'student'));
    }
}
