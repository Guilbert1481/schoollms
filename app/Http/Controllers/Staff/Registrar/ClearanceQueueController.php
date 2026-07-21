<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\ClearanceItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registrar management of student clearances: the queue of open clearances,
 * and per-item sign-off (cleared / hold / back to pending). The clearance's
 * own status is re-derived from its items after every action.
 */
class ClearanceQueueController extends Controller
{
    public function index()
    {
        return view('registrar.clearances.index', [
            'clearances' => Clearance::query()
                ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
                ->orderByDesc('id')
                ->with(['student.user', 'enrollment'])
                ->withCount([
                    'items',
                    'items as cleared_items_count' => fn ($q) => $q->where('status', ClearanceItem::STATUS_CLEARED),
                ])
                ->get(),
        ]);
    }

    public function show(Clearance $clearance)
    {
        $this->guardSchool($clearance->school_id);

        return view('registrar.clearances.show', [
            'clearance' => $clearance->load(['student.user', 'enrollment', 'items' => fn ($q) => $q->orderBy('id')]),
        ]);
    }

    public function updateItem(Request $request, Clearance $clearance, ClearanceItem $item)
    {
        $this->guardSchool($clearance->school_id);
        abort_unless((int) $item->clearance_id === (int) $clearance->id, 404);

        $validated = $request->validate([
            'action' => ['required', Rule::in([ClearanceItem::STATUS_CLEARED, ClearanceItem::STATUS_HOLD, ClearanceItem::STATUS_PENDING])],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update([
            'status' => $validated['action'],
            'acted_by' => auth()->id(),
            'acted_at' => now(),
            'remarks' => $validated['remarks'] ?? $item->remarks,
        ]);

        $clearance->refreshStatus();

        return back()->with('success', '"'.$item->label.'" marked '.str_replace('_', ' ', $validated['action']).'.');
    }

    private function guardSchool(int $schoolId): void
    {
        abort_unless((int) $schoolId === (int) auth()->user()->school_id || auth()->user()->isSuperadmin(), 404);
    }
}
