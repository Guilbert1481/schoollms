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

        return view('admission.settings.enrollment_settings', compact(
            'settings',
            'years',
            'terms',
            'types',
            'upcomingTerms'
        ));
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'title' => 'required',
        'academic_year_id' => 'required',
        'term_id' => 'required',
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

    // Notify every student in the same school that enrolment is open. The
    // notification is auto-dismissed once the student's StudentEnrollment for
    // this setting reaches the "enrolled" status (see model boot()).
    $this->notifyStudents($setting);

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