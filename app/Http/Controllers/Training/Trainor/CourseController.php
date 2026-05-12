<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentSetting;
use App\Models\TrainingSubject;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $sessions = EnrollmentSetting::query()
            ->with(['enrollmentType'])
            ->orderByDesc('start_date')
            ->get();

        return view('training.trainor.courses', [
            'sessions' => $sessions,
        ]);
    }

    public function show(Request $request, int $course)
    {
        $setting = EnrollmentSetting::with('enrollmentType')->findOrFail($course);

        $catalog = \App\Helpers\CourseCatalog::forSession($setting);
        $labels  = config('course_catalog.category_labels', []);

        $programs = [];
        foreach (($catalog['programs'] ?? []) as $key => $program) {
            $programs[$key] = [
                'code'       => $program['code'] ?? strtoupper($key),
                'name'       => $program['name'] ?? ucfirst($key),
                'categories' => $program['categories'] ?? ['all'],
            ];
        }

        $subjects = TrainingSubject::where('enrollment_setting_id', $setting->id)
            ->orderBy('name')
            ->get();

        return view('training.trainor.course_show', [
            'setting'  => $setting,
            'programs' => $programs,
            'labels'   => $labels,
            'subjects' => $subjects,
        ]);
    }

    public function storeSubject(Request $request, int $course)
    {
        $setting = EnrollmentSetting::findOrFail($course);

        $data = $request->validate([
            'program_key' => 'nullable|string|max:100',
            'category'    => 'nullable|string|max:100',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'topics'      => 'nullable|integer|min:0',
            'questions'   => 'nullable|integer|min:0',
            'amount'      => 'nullable|numeric|min:0',
        ]);

        TrainingSubject::create(array_merge($data, [
            'enrollment_setting_id' => $setting->id,
            'created_by'            => $request->user()?->id,
            'topics'                => $data['topics']    ?? 0,
            'questions'             => $data['questions'] ?? 0,
            'amount'                => $data['amount']    ?? 0,
        ]));

        return back()->with('success', 'Subject created.');
    }

    public function destroySubject(Request $request, int $course, int $subject)
    {
        $row = TrainingSubject::where('enrollment_setting_id', $course)
            ->findOrFail($subject);
        $row->delete();

        return back()->with('success', 'Subject deleted.');
    }
}
