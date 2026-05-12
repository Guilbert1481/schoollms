<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EnrollmentType;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\TermCreatedNotification;
use Illuminate\Support\Facades\Notification;

class TermController extends Controller
{
    public function indexTerm()
    {
        $schoolId = auth()->user()->school_id;

        $academicYears = AcademicYear::where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->get();

        $terms = Term::where('school_id', $schoolId)
            ->orderBy('start_date')
            ->get();

        $types = EnrollmentType::orderBy('name')->get();
        
        return view('school.settings.term-settings', compact(
            'academicYears',
            'terms',
            'types'
        ));
    }

    public function storeTerm(Request $request, int $academicYearId)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('id', $academicYearId)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'enrollment_type' => ['required'],
            'term'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validated['start_date'] < $ay->start_date || $validated['end_date'] > $ay->end_date) {
            return back()->with('error', 'Term dates must be within the academic year range.');
        }

        $startDate = Carbon::parse($validated['start_date']);
        $monthYear = $startDate->format('F Y');

        if ($validated['enrollment_type'] === 'special') {

            if (empty($validated['title'])) {
                return back()->with('error', 'Title is required for special enrollment.');
            }

            $name = trim("{$validated['title']} {$validated['term']} {$monthYear}");

        } else {
            $name = $this->makeTermName($validated['term'], $ay->name);
        }

        $exists = Term::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This term already exists.');
        }

        $term = Term::create([
            'school_id'        => $schoolId,
            'academic_year_id' => $academicYearId,
            'academic_year'    => $ay->name,
            'title'            => $validated['title'] ?? null,
            'enrollment_type'  => $validated['enrollment_type'],
            'term'             => $validated['term'],
            'name'             => $name,
            'start_date'       => $validated['start_date'],
            'end_date'         => $validated['end_date'],
            'status'           => 'upcoming',
            'is_current'       => 0,
            'is_active'        => 0,
        ]);

        // Get all active admission users
        $admissions = User::whereHas('accountAccess', function ($q) {
            $q->where('role_id', 2) // 🔁 replace with your actual admission role_id
            ->where('is_active', 1);
        })->get();

        // Send notification
        Notification::send($admissions, new TermCreatedNotification($term));

        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Term created.');
    }

    public function updateTerm(Request $request, int $academicYearId, int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $term = Term::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'enrollment_type' => ['required'],
            'term' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $term->update([
            'enrollment_type' => $validated['enrollment_type'],
            'term'       => $validated['term'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
        ]);

        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Term updated.');
    }

    public function destroyTerm(int $academicYearId, int $id)
    {
        $schoolId = auth()->user()?->school_id;

        $term = Term::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('id', $id)
            ->firstOrFail();

        if ($term->status === 'active') {
            return back()->with('error', 'Cannot delete active term.');
        }

        $term->delete();

        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Term deleted.');
    }

    public function activateTerm(int $academicYearId, int $id)
    {
        $schoolId = auth()->user()?->school_id;

        DB::transaction(function () use ($schoolId, $academicYearId, $id) {

            Term::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->update([
                    'is_active' => 0,
                    'is_current' => 0,
                    'status' => 'upcoming'
                ]);

            $term = Term::findOrFail($id);

            $term->update([
                'is_active' => 1,
                'is_current' => 1,
                'status' => 'active'
            ]);
        });

        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Term activated.');
    }

    public function deactivateTerm(int $academicYearId, int $id)
    {
        $term = Term::findOrFail($id);

        $term->update([
            'is_active' => 0,
            'is_current' => 0,
            'status' => 'upcoming'
        ]);

        return back()->with('success', 'Term deactivated.');
    }

    private function syncTermStatuses($schoolId, $academicYearId)
    {
        $today = now()->toDateString();

        Term::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->each(function ($term) use ($today) {

                if ($term->end_date < $today) {
                    $term->status = 'closed';
                    $term->is_active = 0;
                    $term->is_current = 0;
                } elseif ($term->start_date > $today) {
                    $term->status = 'upcoming';
                    $term->is_active = 0;
                    $term->is_current = 0;
                } else {
                    $term->status = 'active';
                    $term->is_active = 1;
                    $term->is_current = 1;
                }

                $term->save();
            });
    }

    private function makeTermName(string $term, string $academicYearName): string
    {
        return ucfirst($term) . ' ' . $academicYearName;
    }
}