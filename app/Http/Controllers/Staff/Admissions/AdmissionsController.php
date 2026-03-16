<?php

namespace App\Http\Controllers\Staff\Admissions;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Student;
use Illuminate\Http\Request;

class AdmissionsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {
        return view('admission.dashboard', [
            'totalApplications' => Application::count(),
            'submitted'        => Application::where('status', 'submitted')->count(),
            'underReview'      => Application::where('status', 'under_review')->count(),
            'approved'         => Application::where('status', 'approved')->count(),
            'students'         => Student::count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Applications
    |--------------------------------------------------------------------------
    */

    public function applications()
    {
        $applications = Application::latest()->paginate(10);

        return view('admission.applications', compact('applications'));
    }

    /*
    |--------------------------------------------------------------------------
    | Students (Applicants)
    |--------------------------------------------------------------------------
    */

    public function students()
    {
        $students = Student::latest()->paginate(10);

        return view('admission.students', compact('students'));
    }

    /*
    |--------------------------------------------------------------------------
    | Interviews
    |--------------------------------------------------------------------------
    */

    public function interviews()
    {
        return view('admission.interviews');
    }

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

    public function documents()
    {
        return view('admission.documents');
    }

    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */

    public function enrollment()
    {
        return view('public.enrolment_form');
    }


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function reports()
    {
        return view('admission.reports');
    }

    /*
    |--------------------------------------------------------------------------
    | Communications
    |--------------------------------------------------------------------------
    */

    public function communications()
    {
        return view('admission.communications');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function settings()
    {
        return view('admission.settings');
    }

    /*
    |--------------------------------------------------------------------------
    | Global Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([]);
        }

        // Search Students
        $students = Student::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'type' => 'student',
                    'url' => route('admission.students')
                ];
            });

        // Search Applications via Student relationship
        $applications = Application::with('student')
            ->whereHas('student', function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->student->first_name . ' ' . $app->student->last_name,
                    'type' => 'application',
                    'url' => route('admission.applications')
                ];
            });

        return response()->json([
            'results' => $students->merge($applications)
        ]);
    }
}
