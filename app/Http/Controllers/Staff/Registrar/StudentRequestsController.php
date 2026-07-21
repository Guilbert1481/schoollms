<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\ModalityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Registrar queue for student requests, tabbed: Modality | Documents.
 * Approving a modality request writes the new modality onto the enrollment.
 * Document requests walk pending → processing → ready → released (or denied).
 */
class StudentRequestsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'documents' ? 'documents' : 'modality';

        $pendingFirst = fn ($q, string $pendingValue) => $q
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [$pendingValue])
            ->orderByDesc('id');

        return view('registrar.requests.index', [
            'tab' => $tab,
            'modalityRequests' => $pendingFirst(ModalityRequest::query(), ModalityRequest::STATUS_PENDING)
                ->with(['student.user', 'fromModality', 'toModality', 'enrollment'])
                ->get(),
            'documentRequests' => $pendingFirst(DocumentRequest::query(), DocumentRequest::STATUS_PENDING)
                ->with(['student.user', 'document'])
                ->get(),
        ]);
    }

    /** Approve or deny a pending modality request. */
    public function decideModality(Request $request, ModalityRequest $modalityRequest)
    {
        $this->guardSchool($modalityRequest->school_id);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'deny'])],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $modalityRequest->isPending()) {
            return back()->withErrors(['action' => 'That request has already been decided.']);
        }

        DB::transaction(function () use ($modalityRequest, $validated) {
            $approved = $validated['action'] === 'approve';

            $modalityRequest->update([
                'status' => $approved ? ModalityRequest::STATUS_APPROVED : ModalityRequest::STATUS_DENIED,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'decision_remarks' => $validated['remarks'] ?? null,
            ]);

            if ($approved && $modalityRequest->enrollment) {
                $modalityRequest->enrollment->update(['modality_id' => $modalityRequest->to_modality_id]);
            }
        });

        return back()->with('success', $validated['action'] === 'approve'
            ? 'Modality request approved — the enrollment has been updated.'
            : 'Modality request denied.');
    }

    /** Move a document request along its lifecycle. */
    public function transitionDocument(Request $request, DocumentRequest $documentRequest)
    {
        $this->guardSchool($documentRequest->school_id);

        $validated = $request->validate([
            'action' => ['required', Rule::in([
                DocumentRequest::STATUS_PROCESSING,
                DocumentRequest::STATUS_READY,
                DocumentRequest::STATUS_RELEASED,
                DocumentRequest::STATUS_DENIED,
            ])],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $documentRequest->canTransitionTo($validated['action'])) {
            return back()->withErrors(['action' => 'That status change is not allowed from "'.$documentRequest->status.'".']);
        }

        $documentRequest->update([
            'status' => $validated['action'],
            'handled_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? $documentRequest->remarks,
            'released_at' => $validated['action'] === DocumentRequest::STATUS_RELEASED ? now() : $documentRequest->released_at,
        ]);

        return back()->with('success', 'Document request marked as '.str_replace('_', ' ', $validated['action']).'.');
    }

    private function guardSchool(int $schoolId): void
    {
        abort_unless((int) $schoolId === (int) auth()->user()->school_id || auth()->user()->isSuperadmin(), 404);
    }
}
