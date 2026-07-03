<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Subject;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function indexAcademicYear()
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404, 'School not found for this user.');

        // Sync statuses based on dates
        $this->syncAcademicYearStatuses($schoolId);

        $academicYears = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('education_level', 'higher_ed')
            ->orderByDesc('start_date')
            ->get();

        $termsByAcademicYearId = Term::query()
            ->with(['subjectOfferings.subject', 'subjectOfferings.program'])
            ->where('school_id', $schoolId)
            ->where('education_level', 'higher_ed')
            ->orderBy('start_date')
            ->get()
            ->groupBy('academic_year_id');

        $subjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $programs = Program::where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // Education-level options for the term form come from the Education
        // Structure Tree (offered roots), so un-ticking a level hides it here too.
        // This is the higher-ed admin, so Basic Education is managed elsewhere.
        $levelRoots = \App\Support\EducationLevels::offeredRoots()
            ->reject(fn ($r) => \App\Support\EducationLevels::isBasic($r->name))
            ->values();

        return view('school.settings.master-data.ay_terms', compact(
            'academicYears',
            'termsByAcademicYearId',
            'subjects',
            'programs',
            'levelRoots'
        ));
    }

    public function storeAcademicYear(Request $request)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        AcademicYear::create([
            'school_id'       => $schoolId,
            'education_level' => 'higher_ed',
            'name'            => $validated['name'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'is_active'       => 0,
            'status'          => 'upcoming',
        ]);

        $this->syncAcademicYearStatuses($schoolId);

        return back()->with('success', 'Academic year created.');
    }

    public function updateAcademicYear(Request $request, int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $ay->update($validated);

        $this->syncAcademicYearStatuses($schoolId);

        return back()->with('success', 'Academic year updated.');
    }

    public function destroyAcademicYear(int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('id', $id)
            ->firstOrFail();

        if ($ay->status === 'active') {
            return back()->with('error', 'You cannot delete the active academic year.');
        }

        $ay->delete();

        $this->syncAcademicYearStatuses($schoolId);

        return back()->with('success', 'Academic year deleted.');
    }

    public function activateAcademicYear(int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $this->syncAcademicYearStatuses($schoolId);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('id', $id)
            ->firstOrFail();

        if ($ay->status === 'closed') {
            return back()->with('error', 'Cannot activate closed academic year.');
        }

        DB::transaction(function () use ($schoolId, $ay) {
            AcademicYear::where('school_id', $schoolId)
                ->where('education_level', 'higher_ed')
                ->update(['is_active' => 0]);

            $ay->is_active = 1;
            $ay->status = 'active';
            $ay->save();
        });

        return back()->with('success', 'Academic year activated.');
    }

    public function deactivateAcademicYear(int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('id', $id)
            ->firstOrFail();

        $ay->is_active = 0;
        $ay->status = 'upcoming';
        $ay->save();

        return back()->with('success', 'Academic year deactivated.');
    }

    private function syncAcademicYearStatuses(int $schoolId): void
    {
        $today = now()->toDateString();

        AcademicYear::where('school_id', $schoolId)
            ->where('education_level', 'higher_ed')
            ->get()
            ->each(function ($ay) use ($today) {

                if ($ay->end_date < $today) {
                    $ay->status = 'closed';
                    $ay->is_active = 0;
                } elseif ($ay->start_date > $today) {
                    $ay->status = 'upcoming';
                    $ay->is_active = 0;
                } else {
                    $ay->status = 'active';
                    $ay->is_active = 1;
                }

                $ay->save();
            });
    }
}