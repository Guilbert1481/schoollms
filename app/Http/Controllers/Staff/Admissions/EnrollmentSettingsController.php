<?php

namespace App\Http\Controllers\Staff\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use App\Models\EnrollmentSetting;
use App\Models\AdmissionExamSetting;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\EnrollmentType;
use App\Models\User;
use App\Notifications\EnrollmentOpenNotification;
use App\Services\ProgramSubjectActivationService;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class EnrollmentSettingsController extends Controller
{
    public function index()
    {
        $settings = EnrollmentSetting::with([
            'academicYear',
            'term',
            'enrollmentType',
            'creator'
        ])->orderBy('start_date', 'desc')->get();

        $today = Carbon::today();

        foreach ($settings as $s) {
            if ($today->lt($s->start_date)) {
                $s->status = 'Upcoming';
                $s->is_open = 0;
            } elseif ($today->between($s->start_date, $s->end_date)) {
                $s->status = 'Active';
                $s->is_open = 1;
            } else {
                $s->status = 'Expired';
                $s->is_open = 0;
            }
        }

        // Upcoming terms for LEFT TABLE
        $upcomingTerms = Term::where('status', 'upcoming')
            ->orderBy('start_date')
            ->get();

        $years = AcademicYear::orderBy('name')->get();
        $terms = Term::orderBy('name')->get();
        $types = EnrollmentType::orderBy('name')->get();

        $examSetting = $this->currentExamSetting();
        $activeTab   = 'enrollment';

        return view('admission.settings.enrollment_settings', compact(
            'settings',
            'years',
            'terms',
            'types',
            'upcomingTerms',
            'examSetting',
            'activeTab'
        ));
    }

    /**
     * Render the same settings page with the "Admission Exam" tab active.
     */
    public function admissionExam()
    {
        $settings = EnrollmentSetting::with([
            'academicYear', 'term', 'enrollmentType', 'creator',
        ])->orderBy('start_date', 'desc')->get();

        $today = Carbon::today();
        foreach ($settings as $s) {
            if ($today->lt($s->start_date)) {
                $s->status = 'Upcoming';
                $s->is_open = 0;
            } elseif ($today->between($s->start_date, $s->end_date)) {
                $s->status = 'Active';
                $s->is_open = 1;
            } else {
                $s->status = 'Expired';
                $s->is_open = 0;
            }
        }
        $upcomingTerms = Term::where('status', 'upcoming')->orderBy('start_date')->get();
        $years         = AcademicYear::orderBy('name')->get();
        $terms         = Term::orderBy('name')->get();
        $types         = EnrollmentType::orderBy('name')->get();
        $examSetting   = $this->currentExamSetting();
        $activeTab     = 'admission-exam';

        return view('admission.settings.enrollment_settings', compact(
            'settings', 'years', 'terms', 'types', 'upcomingTerms',
            'examSetting', 'activeTab'
        ));
    }

    /**
     * Persist the admission-exam policy for the current school.
     */
    public function admissionExamUpdate(Request $request)
    {
        $validated = $request->validate([
            'require_for_new_student'      => 'sometimes|boolean',
            'require_for_transferee'       => 'sometimes|boolean',
            'require_for_returnee'         => 'sometimes|boolean',
            'require_for_shiftee'          => 'sometimes|boolean',
            'exam_purpose'                 => 'required|in:diagnostic_only,admission_requirement',
            'grants_scholarship'           => 'sometimes|boolean',
            'max_score'                    => 'required|integer|min:1|max:1000',
            'passing_score'                => 'nullable|integer|min:0|lte:max_score',
            'max_attempts'                 => 'required|integer|min:1|max:10',
            'retake_cooldown_days'         => 'nullable|integer|min:0|max:365',
            'result_validity_months'       => 'nullable|integer|min:0|max:120',
            'allow_program_head_waiver'    => 'sometimes|boolean',
            'notify_applicant_on_schedule' => 'sometimes|boolean',
            'auto_assess_after_pass'       => 'sometimes|boolean',
            'instructions'                 => 'nullable|string|max:5000',
            'scholarship_bands'                 => 'nullable|array',
            'scholarship_bands.*.label'         => 'nullable|string|max:100',
            'scholarship_bands.*.percent'       => 'nullable|numeric|min:0|max:100',
            'scholarship_bands.*.apply_to'      => 'nullable|in:tuition,total',
            'scholarship_bands.*.min_score'     => 'nullable|integer|min:0',
        ]);

        // When the exam is a hard requirement, passing_score is mandatory.
        if ($validated['exam_purpose'] === AdmissionExamSetting::PURPOSE_REQUIREMENT
            && empty($validated['passing_score'])) {
            return back()
                ->withErrors(['passing_score' => 'Passing score is required when the exam is an admission requirement.'])
                ->withInput();
        }

        // Normalise booleans (unchecked checkboxes are absent from the request).
        foreach ([
            'require_for_new_student', 'require_for_transferee',
            'require_for_returnee', 'require_for_shiftee',
            'allow_program_head_waiver', 'notify_applicant_on_schedule',
            'auto_assess_after_pass', 'grants_scholarship',
        ] as $flag) {
            $validated[$flag] = (bool) ($request->input($flag, false));
        }

        // Keep only complete scholarship bands (a percent + a minimum score),
        // normalised. Stored as JSON; only meaningful for the scholarship purpose.
        $validated['scholarship_bands'] = collect($request->input('scholarship_bands', []))
            ->filter(fn ($b) => is_numeric($b['percent'] ?? null) && is_numeric($b['min_score'] ?? null))
            ->map(fn ($b) => [
                'label'     => trim((string) ($b['label'] ?? '')) ?: (((float) $b['percent']).'% scholarship'),
                'percent'   => round((float) $b['percent'], 2),
                'apply_to'  => in_array($b['apply_to'] ?? null, ['tuition', 'total'], true) ? $b['apply_to'] : 'total',
                'min_score' => (int) $b['min_score'],
            ])
            ->sortByDesc('min_score')
            ->values()
            ->all();

        AdmissionExamSetting::updateOrCreate(
            ['school_id' => auth()->user()->school_id],
            $validated
        );

        return redirect()
            ->route('admission.enrollment-settings.admission-exam')
            ->with('success', 'Admission exam settings saved.');
    }

    /**
     * Fetch the (singleton) admission-exam settings for the current school,
     * returning an unsaved default model if none exists yet.
     */
    protected function currentExamSetting(): AdmissionExamSetting
    {
        $schoolId = auth()->user()->school_id;

        return AdmissionExamSetting::firstOrNew(
            ['school_id' => $schoolId],
            [
                'require_for_new_student'      => false,
                'require_for_transferee'       => false,
                'require_for_returnee'         => false,
                'require_for_shiftee'          => false,
                'exam_purpose'                 => AdmissionExamSetting::PURPOSE_DIAGNOSTIC,
                'max_score'                    => 100,
                'passing_score'                => null,
                'max_attempts'                 => 1,
                'retake_cooldown_days'         => null,
                'result_validity_months'       => null,
                'allow_program_head_waiver'    => true,
                'notify_applicant_on_schedule' => true,
                'auto_assess_after_pass'       => false,
                'instructions'                 => null,
            ]
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'title' => 'required',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'nullable|image|max:20480',
            'instructor_title' => 'nullable|string|max:50',
            'instructor_name' => 'nullable|string|max:255',
            'course_details' => 'nullable|string',
        ]);

    $coverPath = null;
    if ($request->hasFile('cover_image')) {
        $coverPath = $this->storeCoverImage($request->file('cover_image'));
    }

    $currency = $request->input('currency')
        ?: CurrencyHelper::forCurrentSchool()['code'];

    $setting = EnrollmentSetting::create([
        'name' => $request->name,
        'title' => $request->title,
        'academic_year_id' => $request->academic_year_id,
        'term_id' => $request->term_id,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'price' => $request->price,
        'currency' => $currency,
        'cover_image' => $coverPath,
        'instructor_title' => $request->instructor_title,
        'instructor_name' => $request->instructor_name,
        'course_details' => $request->course_details,
        'created_by' => auth()->id(),
    ]);

    // Auto-activate program_subjects if the enrollment window already includes today.
    app(ProgramSubjectActivationService::class)->sync();

    // Dismiss any "term_created" bell notifications for this admission user
    // that point at the term/AY they just opened an enrollment for. This is
    // what makes the bell badge go away once the session date is set.
    $user = auth()->user();
    if ($user) {
        $termId = (int) $request->term_id;
        $ayId   = (int) $request->academic_year_id;

        \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->where(function ($q) use ($termId, $ayId) {
                $q->where('data', 'like', '%"type":"term_created"%')
                  ->where(function ($qq) use ($termId, $ayId) {
                      $qq->where('data', 'like', '%"term_id":' . $termId . '%')
                         ->orWhere('data', 'like', '%"academic_year_id":' . $ayId . '%');
                  });
            })
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    // Note: student bell notifications are now derived virtually from
    // EnrollmentSetting rows (see App\Support\EnrollmentNotifications). This
    // ensures even students created AFTER the setting was made will see the
    // notification, and that it auto-disappears when end_date passes or the
    // student submits an application.

    return redirect()->back()->with('success', 'Enrollment setting created.');
}

    public function destroy($id)
    {
        EnrollmentSetting::findOrFail($id)->delete();
        return back()->with('success', 'Enrollment session deleted.');
    }

    /**
     * Send the "enrollment is open" bell notification to every student in the
     * same school as the setting's term. Skips students who already have an
     * "enrolled" StudentEnrollment for this setting.
     */
    protected function notifyStudents(EnrollmentSetting $setting): void
    {
        $term = $setting->term ?: Term::find($setting->term_id);
        if (! $term || ! $term->school_id) {
            return;
        }

        $students = User::where('role', 'student')
            ->where('school_id', $term->school_id)
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        Notification::send($students, new EnrollmentOpenNotification($setting));
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'title' => 'required',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'price' => 'nullable|numeric|min:0',
        'cover_image' => 'nullable|image|max:20480',
        'instructor_title' => 'nullable|string|max:50',
        'instructor_name' => 'nullable|string|max:255',
        'course_details' => 'nullable|string',
    ]);

    $setting = EnrollmentSetting::findOrFail($id);

    $data = [
        'title' => $request->title,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'price' => $request->price,
        'currency' => $request->input('currency')
            ?: ($setting->currency ?: CurrencyHelper::forCurrentSchool()['code']),
        'instructor_title' => $request->instructor_title,
        'instructor_name' => $request->instructor_name,
        'course_details' => $request->course_details,
        'updated_by' => auth()->id(),
    ];

    if ($request->hasFile('cover_image')) {
        if ($setting->cover_image && Storage::disk('public')->exists($setting->cover_image)) {
            Storage::disk('public')->delete($setting->cover_image);
        }
        $data['cover_image'] = $this->storeCoverImage($request->file('cover_image'));
    }

    $setting->update($data);

    // Re-sync activation in case dates changed.
    app(ProgramSubjectActivationService::class)->sync();

    return back()->with('success', 'Enrollment session updated.');
}

    /**
     * Resize and compress an uploaded cover image so it fits common
     * card dimensions and stays under a reasonable file size.
     */
    protected function storeCoverImage(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $dir  = 'enrollment/covers';

        $filename = Str::uuid().'.jpg';
        $relative = $dir.'/'.$filename;

        try {
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($file->getRealPath())
                ->scaleDown(width: 1600, height: 1600);

            $binary = (string) $image->toJpeg(quality: 82);

            $disk->put($relative, $binary);

            return $relative;
        } catch (\Throwable $e) {
            // Fallback: store the original file as-is if image processing fails.
            return $file->store($dir, 'public');
        }
    }
}