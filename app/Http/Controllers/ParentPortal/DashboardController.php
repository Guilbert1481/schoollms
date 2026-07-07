<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ParentPortal\Concerns\ResolvesChildren;
use App\Support\EnrollmentStatuses;
use Illuminate\Support\Facades\DB;

/**
 * Parent portal home: one card per linked child (the family at a glance).
 * Phase 1 shell — the per-child read surface (grades, schedule, billing
 * detail) hangs off the child selected here.
 */
class DashboardController extends Controller
{
    use ResolvesChildren;

    public function index()
    {
        $children = $this->linkedChildren();
        $activeChildId = optional($this->activeChild())->id;

        $studentIds = $children->pluck('id')->all();

        // Latest enrollment per child (status pill) — one query, grouped.
        $enrollments = DB::table('student_enrollments')
            ->whereIn('student_id', $studentIds ?: [0])
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'status', 'year_level', 'payment_due_at'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->first());

        // Outstanding balance per child. Billing keys off the child's LOGIN
        // user (invoices.student_id → users.id), so map through students.user_id.
        $userIdByStudent = $children->whereNotNull('user_id')->pluck('user_id', 'id');
        $balances = DB::table('invoices')
            ->whereIn('student_id', $userIdByStudent->values()->all() ?: [0])
            ->where('balance', '>', 0.005)
            ->groupBy('student_id')
            ->select('student_id', DB::raw('SUM(balance) as outstanding'), DB::raw('MIN(due_date) as next_due'))
            ->get()
            ->keyBy('student_id');

        $cards = $children->map(function ($c) use ($enrollments, $userIdByStudent, $balances) {
            $enrollment = $enrollments->get($c->id);
            $billing    = $balances->get($userIdByStudent->get($c->id));

            return (object) [
                'id'             => $c->id,
                'name'           => trim($c->first_name.' '.$c->last_name),
                'student_number' => $c->student_number ?: '—',
                'school_name'    => $c->school_name,
                'relationship'   => $c->relationship ? ucfirst((string) $c->relationship) : null,
                'year_level'     => $enrollment && $enrollment->year_level !== null
                    ? 'Level '.(int) $enrollment->year_level
                    : null,
                'status_pill'    => $enrollment
                    ? EnrollmentStatuses::pillForDb($enrollment->status)
                    : null,
                'outstanding'    => $billing ? (float) $billing->outstanding : 0.0,
                'next_due'       => $billing->next_due ?? null,
            ];
        });

        return view('parent.dashboard', [
            'cards'         => $cards,
            'activeChildId' => $activeChildId,
        ]);
    }

    /** Child switcher: remember the selected child for the per-child pages. */
    public function selectChild(int $student)
    {
        $child = $this->authorizeChild($student);

        session(['parent_active_child_id' => (int) $child->id]);

        return back()->with('status', trim($child->first_name.' '.$child->last_name).' selected.');
    }
}
