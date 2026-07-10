<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use App\Notifications\TermCreatedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Principal — Basic Ed AY & Sessions.
 *
 * Basic education has ONE enrollment session per school year (no regular
 * semester terms). The principal may additionally open special non-academic
 * sessions inside the same AY (Training, Short Course, Review, Seminar).
 *
 * All records are scoped to education_level = 'basic_ed' so admin's
 * Master Data > AY & Terms (higher_ed) stays separate.
 */
class AcademicYearController extends Controller
{
    public const LEVEL = 'basic_ed';

    public const SPECIAL_TERMS = ['Training', 'Short Course', 'Review', 'Seminar'];

    public function index()
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $this->syncAcademicYearStatuses($schoolId);

        $academicYears = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->orderByDesc('start_date')
            ->get();

        $termsByAcademicYearId = Term::query()
            ->where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->orderBy('start_date')
            ->get()
            ->groupBy('academic_year_id');

        // Admin-created (higher_ed) academic years — the Principal's Create-AY
        // modal fetches these so the basic-ed year LABEL stays consistent with
        // whatever the admin "opened" on Master Data > AY & Terms. Newest first.
        $adminAcademicYears = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('education_level', 'higher_ed')
            ->orderByDesc('start_date')
            ->get();

        // Reusable-table view model. AYs are rows; special sessions live in a
        // per-AY drawer (openSessionsModal). Status/actions are raw cells; every
        // onclick handler takes ONLY the AY id and looks the rest up from the
        // JSON blobs below — so no fragile string args in server-built HTML.
        $columns = [
            ['key' => 'ay',      'label' => 'Academic Year', 'width' => '220px', 'raw' => true],
            ['key' => 'start',   'label' => 'Start',         'width' => '120px'],
            ['key' => 'end',     'label' => 'End',           'width' => '120px'],
            ['key' => 'status',  'label' => 'Status',        'width' => '110px', 'raw' => true],
            ['key' => 'actions', 'label' => 'Actions',       'width' => '340px', 'raw' => true],
        ];

        $rows = $academicYears->map(function (AcademicYear $ay) use ($termsByAcademicYearId) {
            $sessionCount = ($termsByAcademicYearId[$ay->id] ?? collect())->count();

            return [
                'id'      => $ay->id,
                'ay'      => '<div class="font-black text-slate-800">'.e($ay->name).'</div>'
                            .'<div class="text-xs font-medium text-slate-500">Academic Year</div>',
                'start'   => Carbon::parse($ay->start_date)->format('Y-m-d'),
                'end'     => Carbon::parse($ay->end_date)->format('Y-m-d'),
                'status'  => $this->statusBadgeHtml($ay->computed_status),
                'actions' => $this->ayActionsHtml($ay, $sessionCount),
            ];
        })->values();

        $aysJson = $academicYears->mapWithKeys(fn (AcademicYear $ay) => [$ay->id => [
            'name'  => $ay->name,
            'start' => Carbon::parse($ay->start_date)->format('Y-m-d'),
            'end'   => Carbon::parse($ay->end_date)->format('Y-m-d'),
        ]]);

        $sessionsJson = $termsByAcademicYearId->map(fn ($sessions) => $sessions->map(fn (Term $t) => [
            'id'     => $t->id,
            'name'   => $t->name,
            'term'   => $t->term,
            'title'  => $t->title,
            'start'  => Carbon::parse($t->start_date)->format('Y-m-d'),
            'end'    => Carbon::parse($t->end_date)->format('Y-m-d'),
            'status' => $t->computed_status,
        ])->values());

        return view('principal.ay-terms.index', [
            'academicYears'         => $academicYears,
            'termsByAcademicYearId' => $termsByAcademicYearId,
            'specialTerms'          => self::SPECIAL_TERMS,
            'adminAcademicYears'    => $adminAcademicYears,
            'columns'               => $columns,
            'rows'                  => $rows,
            'aysJson'               => $aysJson,
            'sessionsJson'          => $sessionsJson,
        ]);
    }

    /* ============================================================
     | Academic Year CRUD
     |============================================================*/

    public function storeAcademicYear(Request $request)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $ay = AcademicYear::create([
            'school_id'       => $schoolId,
            'education_level' => self::LEVEL,
            'name'            => $validated['name'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'is_active'       => 0,
            'status'          => 'upcoming',
        ]);

        $this->syncAcademicYearStatuses($schoolId);

        // Create a placeholder enrollment Term so the AY surfaces in admission's
        // "Upcoming Session" list immediately, even before activation.
        $enrollmentTerm = $this->ensureEnrollmentTerm($schoolId, $ay);

        $this->notifyAdmission($schoolId, $enrollmentTerm);

        return back()->with('success', 'Basic-ed academic year created.');
    }

    public function updateAcademicYear(Request $request, int $id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
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

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('id', $id)
            ->firstOrFail();

        if ($ay->status === 'active') {
            return back()->with('error', 'You cannot delete the active academic year.');
        }

        DB::transaction(function () use ($ay) {
            Term::where('academic_year_id', $ay->id)->delete();
            $ay->delete();
        });

        $this->syncAcademicYearStatuses($schoolId);

        return back()->with('success', 'Academic year deleted.');
    }

    public function activateAcademicYear(int $id)
    {
        $schoolId = auth()->user()?->school_id;

        $this->syncAcademicYearStatuses($schoolId);

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('id', $id)
            ->firstOrFail();

        if ($ay->status === 'closed') {
            return back()->with('error', 'Cannot activate a closed academic year.');
        }

        $enrollmentTerm = null;

        DB::transaction(function () use ($schoolId, $ay, &$enrollmentTerm) {
            AcademicYear::where('school_id', $schoolId)
                ->where('education_level', self::LEVEL)
                ->update(['is_active' => 0]);

            $ay->is_active = 1;
            $ay->status    = 'active';
            $ay->save();

            // Auto-activate all basic-ed subjects so admission can open
            // enrollment immediately for the active AY.
            DB::table('subjects')
                ->where('school_id', $schoolId)
                ->where('is_basic_ed', 1)
                ->update(['is_active' => 1, 'updated_at' => now()]);

            $enrollmentTerm = $this->ensureEnrollmentTerm($schoolId, $ay);
        });

        $this->syncTermStatuses($schoolId, $ay->id);

        $this->notifyAdmission($schoolId, $enrollmentTerm ?: $ay);

        return back()->with('success', 'Basic-ed academic year activated. Subjects are now open for admission.');
    }

    public function deactivateAcademicYear(int $id)
    {
        $schoolId = auth()->user()?->school_id;

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('id', $id)
            ->firstOrFail();

        $ay->is_active = 0;
        $ay->status    = 'upcoming';
        $ay->save();

        return back()->with('success', 'Academic year deactivated.');
    }

    /* ============================================================
     | Special Sessions (non-academic terms inside the basic-ed AY)
     |============================================================*/

    public function storeSession(Request $request, int $academicYearId)
    {
        $schoolId = auth()->user()?->school_id;

        $ay = AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('id', $academicYearId)
            ->firstOrFail();

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'term'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if (!in_array($validated['term'], self::SPECIAL_TERMS, true)) {
            return back()->with('error', 'Invalid session type.');
        }

        if ($validated['start_date'] < $ay->start_date || $validated['end_date'] > $ay->end_date) {
            return back()->with('error', 'Session dates must be within the academic year range.');
        }

        $monthYear = Carbon::parse($validated['start_date'])->format('F Y');
        $name      = trim("{$validated['title']} {$validated['term']} {$monthYear}");

        $exists = Term::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('academic_year_id', $academicYearId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This session already exists.');
        }

        $term = Term::create([
            'school_id'        => $schoolId,
            'education_level'  => self::LEVEL,
            'academic_year_id' => $academicYearId,
            'academic_year'    => $ay->name,
            'title'            => $validated['title'],
            'enrollment_type'  => 'special',
            'term'             => $validated['term'],
            'name'             => $name,
            'start_date'       => $validated['start_date'],
            'end_date'         => $validated['end_date'],
            'status'           => 'upcoming',
            'is_current'       => 0,
            'is_active'        => 0,
        ]);

        $this->syncTermStatuses($schoolId, $academicYearId);

        $this->notifyAdmission($schoolId, $term);

        return back()->with('success', 'Special session created.');
    }

    /* ============================================================
     | Reusable-table cell renderers (raw HTML; ids only in onclick)
     |============================================================*/

    private function statusBadgeHtml(string $status): string
    {
        return match ($status) {
            'active'   => '<span class="inline-flex rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">Active</span>',
            'upcoming' => '<span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">Upcoming</span>',
            default    => '<span class="inline-flex rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">Closed</span>',
        };
    }

    private function ayActionsHtml(AcademicYear $ay, int $sessionCount): string
    {
        $id  = (int) $ay->id;
        $btn = 'px-3 py-1.5 rounded-lg text-xs font-extrabold border';

        $html = '<div class="flex items-center gap-2 flex-wrap">';

        if ($ay->is_active) {
            $html .= '<form method="POST" action="'.route('principal.ay-terms.academic_year.deactivate', $id).'" class="inline">'
                .csrf_field()
                .'<button class="'.$btn.' border-amber-200 bg-amber-50 text-amber-800">Deactivate</button></form>';
        } elseif ($ay->can_activate) {
            $html .= '<form method="POST" action="'.route('principal.ay-terms.academic_year.activate', $id).'" class="inline">'
                .csrf_field()
                .'<button class="'.$btn.' border-emerald-200 bg-emerald-50 text-emerald-800">Activate</button></form>';
        }

        $html .= '<button type="button" onclick="openSessionsModal('.$id.')" class="'.$btn.' border-slate-200 text-slate-700">Sessions ('.$sessionCount.')</button>';
        $html .= '<button type="button" onclick="openCreateSessionModal('.$id.')" class="'.$btn.' border-slate-200 text-slate-700">+ Special Session</button>';
        $html .= '<button type="button" onclick="openEditAYModal('.$id.')" class="'.$btn.' border-indigo-200 bg-indigo-50 text-indigo-800">Edit</button>';
        $html .= '<form method="POST" action="'.route('principal.ay-terms.academic_year.destroy', $id).'" class="inline" data-confirm="Delete this academic year? Its sessions will be removed too.">'
            .csrf_field().method_field('DELETE')
            .'<button class="'.$btn.' border-red-200 bg-red-50 text-red-800">Delete</button></form>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Ensure a placeholder "regular" enrollment Term exists for the given
     * basic-ed AY. This is what surfaces in admission's "Upcoming Session"
     * list and what the EnrollmentSetting form binds to.
     */
    protected function ensureEnrollmentTerm(int $schoolId, AcademicYear $ay): Term
    {
        return Term::firstOrCreate(
            [
                'school_id'        => $schoolId,
                'education_level'  => self::LEVEL,
                'academic_year_id' => $ay->id,
                'enrollment_type'  => 'regular',
            ],
            [
                'academic_year' => $ay->name,
                'title'         => 'Basic-Ed Enrollment',
                'term'          => 'Enrollment',
                'name'          => 'Basic Ed (AY ' . $ay->name . ')',
                'start_date'    => $ay->start_date,
                'end_date'      => $ay->end_date,
                'status'        => 'upcoming',
                'is_current'    => 0,
                'is_active'     => 0,
            ]
        );
    }

    /**
     * Notify all admission managers in this school that an enrollment-bearing
     * record (AY or special session) is ready. The bell partial routes clicks
     * on type === 'term_created' to /admission/enrollment-settings.
     */
    protected function notifyAdmission(?int $schoolId, $record): void
    {
        if (!$schoolId || !$record) {
            return;
        }

        $admissions = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'admission_manager')
            ->get();

        if ($admissions->isEmpty()) {
            return;
        }

        Notification::send($admissions, new TermCreatedNotification($record));
    }

    public function updateSession(Request $request, int $academicYearId, int $id)
    {
        $schoolId = auth()->user()?->school_id;

        $term = Term::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('academic_year_id', $academicYearId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'term'       => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $term->update([
            'title'      => $validated['title'],
            'term'       => $validated['term'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
        ]);

        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Session updated.');
    }

    public function destroySession(int $academicYearId, int $id)
    {
        $schoolId = auth()->user()?->school_id;

        $term = Term::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('academic_year_id', $academicYearId)
            ->where('id', $id)
            ->firstOrFail();

        if ($term->status === 'active') {
            return back()->with('error', 'Cannot delete an active session.');
        }

        $term->delete();
        $this->syncTermStatuses($schoolId, $academicYearId);

        return back()->with('success', 'Session deleted.');
    }

    /* ============================================================
     | Status sync helpers
     |============================================================*/

    private function syncAcademicYearStatuses(int $schoolId): void
    {
        $today = now()->toDateString();

        AcademicYear::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->get()
            ->each(function ($ay) use ($today) {
                if ($ay->end_date < $today) {
                    $ay->status    = 'closed';
                    $ay->is_active = 0;
                } elseif ($ay->start_date > $today) {
                    $ay->status    = 'upcoming';
                    $ay->is_active = 0;
                } else {
                    $ay->status    = 'active';
                    $ay->is_active = 1;
                }
                $ay->save();
            });
    }

    private function syncTermStatuses(int $schoolId, int $academicYearId): void
    {
        $today = now()->toDateString();

        Term::where('school_id', $schoolId)
            ->where('education_level', self::LEVEL)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->each(function ($term) use ($today) {
                if ($term->end_date < $today) {
                    $term->status     = 'closed';
                    $term->is_active  = 0;
                    $term->is_current = 0;
                } elseif ($term->start_date > $today) {
                    $term->status     = 'upcoming';
                    $term->is_active  = 0;
                    $term->is_current = 0;
                } else {
                    $term->status     = 'active';
                    $term->is_active  = 1;
                    $term->is_current = 1;
                }
                $term->save();
            });
    }
}
