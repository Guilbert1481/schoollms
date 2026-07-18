<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentDocument;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves sensitive uploaded documents (government IDs, registrar-required
 * enrollment documents) from the PRIVATE disk through an authorization gate.
 *
 * Access rule (both endpoints): the owning student's own account, or a
 * same-school staff member (admin / registrar / admission_manager /
 * finance_manager), or superadmin. Everyone else gets 404 — never 403, so
 * the existence of a document is not leaked. (Roadmap C2.)
 */
class SecureDocumentController extends Controller
{
    /** Government-ID image/PDF uploaded on enrollment step 1 (students.photo_id). */
    public function studentId(Student $student): Response
    {
        // Owner, same-school staff, or superadmin — 404 otherwise. Rule lives
        // in StudentPolicy::viewDocuments (A1); the BelongsToSchool global
        // scope already filters the binding, this is the explicit check.
        abort_unless(auth()->user()->can('viewDocuments', $student), 404);

        return $this->serve($student->photo_id);
    }

    /** Registrar-required document (transcript, birth certificate, …). */
    public function enrollment(EnrollmentDocument $document): Response
    {
        abort_unless(auth()->user()->can('view', $document), 404); // EnrollmentDocumentPolicy (A1)

        return $this->serve($document->file_path);
    }

    /**
     * Stream the file from the private disk; fall back to the legacy public
     * location for files not yet moved by `documents:relocate-private`.
     */
    private function serve(?string $path): Response
    {
        abort_if(blank($path), 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }
}
