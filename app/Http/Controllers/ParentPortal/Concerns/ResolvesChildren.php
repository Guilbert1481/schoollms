<?php

namespace App\Http\Controllers\ParentPortal\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * THE parent-portal ownership chokepoint. Every parent controller resolves
 * children exclusively through these helpers so the access rule lives in one
 * place: an ACTIVE parent_student link AND an active, approved (subscribed)
 * school. `role:parent` middleware alone never authorizes a specific child —
 * skipping authorizeChild() on a child-scoped route is an IDOR.
 *
 * DB-join reads (house pattern, Larastan-safe).
 */
trait ResolvesChildren
{
    /** All children this parent may currently see. */
    protected function linkedChildren(): Collection
    {
        return DB::table('parent_student')
            ->join('students', 'students.id', '=', 'parent_student.student_id')
            ->join('schools', 'schools.id', '=', 'students.school_id')
            ->where('parent_student.parent_user_id', (int) auth()->id())
            ->where('parent_student.access_status', 'active')
            ->where('schools.is_active', 1)
            ->where('schools.approval_status', 'approved')
            ->orderBy('students.first_name')
            ->orderBy('students.id')
            ->get([
                'students.id',
                'students.user_id',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.school_id',
                'schools.school_name',
                'parent_student.relationship',
                'parent_student.is_primary',
            ]);
    }

    /**
     * The student must be one of this parent's visible children — 404
     * otherwise (never 403: an existing-but-foreign id must be
     * indistinguishable from a nonexistent one).
     */
    protected function authorizeChild(int $studentId): object
    {
        $child = $this->linkedChildren()->firstWhere('id', $studentId);

        abort_if(! $child, 404);

        return $child;
    }

    /** The currently selected child (session choice, else the first). */
    protected function activeChild(): ?object
    {
        $children = $this->linkedChildren();

        return $children->firstWhere('id', (int) session('parent_active_child_id'))
            ?? $children->first();
    }
}
