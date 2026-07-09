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
    private const STAFF_ROLES = ['admin', 'registrar', 'admission_manager', 'finance_manager'];

    /** Government-ID image/PDF uploaded on enrollment step 1 (students.photo_id). */
    public function studentId(Student $student): Response
    {
        $this->authorizeAccess($student);

        return $this->serve($student->photo_id);
    }

    /** Registrar-required document (transcript, birth certificate, …). */
    public function enrollment(EnrollmentDocument $document): Response
    {
        $student = $document->enrollment?->student;
        abort_unless($student !== null, 404);

        $this->authorizeAccess($student, (int) $document->school_id);

        return $this->serve($document->file_path);
    }

    /**
     * Owner, same-school staff, or superadmin — 404 otherwise.
     * The BelongsToSchool global scope already filters the binding; this is
     * the explicit belt-and-suspenders check the governance docs require.
     */
    private function authorizeAccess(Student $student, ?int $schoolId = null): void
    {
        $user = auth()->user();
        $schoolId ??= (int) $student->school_id;

        $isOwner = (int) $student->user_id === (int) $user->id;

        $isStaff = in_array($user->role, self::STAFF_ROLES, true)
            && (int) $user->school_id === $schoolId;

        abort_unless($isOwner || $isStaff || $user->isSuperadmin(), 404);
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
