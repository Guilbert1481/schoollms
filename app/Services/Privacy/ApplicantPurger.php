<?php

namespace App\Services\Privacy;

use App\Models\EnrollmentDocument;
use App\Models\EnrollmentDraft;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAcademicBackground;
use App\Models\StudentEnrollment;
use App\Models\StudentHealthRecord;
use App\Models\User;
use App\Support\AuditTrail;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Roadmap D3 — erases the PII of applicants who never enrolled, once past the
 * retention window (config/privacy.php, RA 10173). Enrollment creates a Student
 * row plus drafts / health / academic / guardian records and ID document files
 * up front, so a rejected or abandoned applicant leaves a full footprint that
 * must not linger past its purpose.
 *
 * A student is a purge candidate ONLY when every safeguard holds:
 *  - no enrollment ever reached a protected status (enrolled / billed / …);
 *  - they are in no class;
 *  - they are an actual applicant (have an enrollment or a saved draft — never
 *    a staff-created skeleton);
 *  - their LAST activity is older than the applicable window (the months
 *    window for a decided application, the shorter days window for a
 *    never-submitted draft).
 *
 * Every purge is recorded to the off-database audit trail (Roadmap S4) so the
 * erasure itself is accountable and survives a DB compromise.
 */
class ApplicantPurger
{
    /** Students due for purge right now, newest-activity last. */
    public function dueForPurge(): Collection
    {
        $protected = config('privacy.protected_enrollment_statuses', []);

        return Student::query()
            ->whereDoesntHave('studentEnrollments', fn ($q) => $q->whereIn('status', $protected))
            ->whereDoesntHave('classes')
            ->where(fn ($q) => $q->has('studentEnrollments')->orHas('drafts'))
            ->with('studentEnrollments:id,student_id,status,updated_at')
            ->get()
            ->filter(fn (Student $s) => $this->isDue($s))
            ->values();
    }

    /** Is this student past the retention window for their situation? */
    public function isDue(Student $student): bool
    {
        return $this->lastActivityAt($student)->lessThanOrEqualTo($this->cutoffFor($student));
    }

    /**
     * Erase (or, in dry-run, tally) one applicant. Returns a summary of what was
     * removed. Deletions run in a single transaction so a partial failure leaves
     * the record intact; the audit line is written only after it commits.
     *
     * @return array<string, mixed>
     */
    public function purge(Student $student, bool $commit): array
    {
        $enrollmentIds = StudentEnrollment::where('student_id', $student->id)->pluck('id');
        $docPaths = EnrollmentDocument::whereIn('enrollment_id', $enrollmentIds)
            ->pluck('file_path')->filter()->values();

        $summary = [
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'reason' => $this->reasonFor($student),
            'mode' => $this->depth(),
            'last_activity' => $this->lastActivityAt($student)->toDateString(),
            'enrollments' => $enrollmentIds->count(),
            'documents' => $docPaths->count(),
            'drafts' => EnrollmentDraft::where('student_id', $student->id)->count(),
            'health_records' => StudentHealthRecord::where('student_id', $student->id)->count(),
            'academic_records' => StudentAcademicBackground::where('student_id', $student->id)->count(),
            'guardians' => Guardian::where('student_id', $student->id)->count(),
            'files_unlinked' => 0,
            'user_deleted' => false,
        ];

        if (! $commit) {
            $summary['files_unlinked'] = $this->countExistingFiles($student, $docPaths);

            return $summary;
        }

        DB::transaction(function () use ($student, $enrollmentIds, $docPaths, &$summary) {
            $summary['files_unlinked'] = $this->deleteFiles($student, $docPaths);

            EnrollmentDocument::whereIn('enrollment_id', $enrollmentIds)->delete();
            EnrollmentDraft::where('student_id', $student->id)->delete();
            StudentHealthRecord::where('student_id', $student->id)->delete();
            StudentAcademicBackground::where('student_id', $student->id)->delete();
            Guardian::where('student_id', $student->id)->delete();
            DB::table('parent_student')->where('student_id', $student->id)->delete();
            StudentEnrollment::where('student_id', $student->id)->delete();

            $userId = $student->user_id;

            if ($this->depth() === 'scrub') {
                $this->scrubStudent($student);
            } else {
                $student->delete();

                if (config('privacy.delete_applicant_user') && $userId) {
                    $summary['user_deleted'] = $this->maybeDeleteUser((int) $userId);
                }
            }
        });

        // After commit only — the erasure is now durable, so record it to the
        // off-database trail (S4). Never let a logging hiccup undo the purge.
        AuditTrail::record('pii_purge', $summary);

        return $summary;
    }

    /* ---------------------------------------------------------------- */

    private function depth(): string
    {
        return config('privacy.purge_depth') === 'scrub' ? 'scrub' : 'hard';
    }

    /** 'decided' applications age on the months window; drafts on the days window. */
    private function reasonFor(Student $student): string
    {
        $decided = config('privacy.decided_dead_end_statuses', []);

        return $student->studentEnrollments->contains(fn ($e) => in_array($e->status, $decided, true))
            ? 'decided_application'
            : 'abandoned_draft';
    }

    private function cutoffFor(Student $student): CarbonInterface
    {
        return $this->reasonFor($student) === 'decided_application'
            ? now()->subMonths((int) config('privacy.applicant_retention_months', 12))
            : now()->subDays((int) config('privacy.abandoned_draft_days', 90));
    }

    /** Most recent of: the student row, any enrollment, any draft. */
    private function lastActivityAt(Student $student): CarbonInterface
    {
        $stamps = collect([
            $student->updated_at,
            $student->studentEnrollments->max('updated_at'),
            EnrollmentDraft::where('student_id', $student->id)->max('updated_at'),
        ])->filter()->map(fn ($t) => $t instanceof CarbonInterface ? $t : Carbon::parse($t));

        return $stamps->isEmpty() ? Carbon::createFromTimestamp(0) : $stamps->max();
    }

    /** @param  Collection<int, string>  $docPaths */
    private function countExistingFiles(Student $student, Collection $docPaths): int
    {
        return $this->idFilePaths($student, $docPaths)
            ->filter(fn ($f) => Storage::disk($f['disk'])->exists($f['path']))
            ->count();
    }

    /** @param  Collection<int, string>  $docPaths */
    private function deleteFiles(Student $student, Collection $docPaths): int
    {
        $deleted = 0;

        foreach ($this->idFilePaths($student, $docPaths) as $file) {
            if (Storage::disk($file['disk'])->exists($file['path'])) {
                Storage::disk($file['disk'])->delete($file['path']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Every uploaded file tied to the applicant, with the disk it lives on:
     * the government-ID document (photo_id) and enrolment documents are on the
     * private `local` disk; the profile avatar (photo_path) is public.
     *
     * @param  Collection<int, string>  $docPaths
     * @return Collection<int, array{disk: string, path: string}>
     */
    private function idFilePaths(Student $student, Collection $docPaths): Collection
    {
        $files = collect();

        if ($student->photo_id) {
            $files->push(['disk' => 'local', 'path' => $student->photo_id]);
        }
        if ($student->photo_path) {
            $files->push(['disk' => 'public', 'path' => $student->photo_path]);
        }
        foreach ($docPaths as $path) {
            $files->push(['disk' => 'local', 'path' => $path]);
        }

        return $files;
    }

    private function scrubStudent(Student $student): void
    {
        $student->forceFill([
            'lrn' => null,
            'first_name' => 'Purged',
            'middle_name' => null,
            'last_name' => 'Applicant',
            'preferred_name' => null,
            'date_of_birth' => null,
            'place_of_birth' => null,
            'blood_type' => null,
            'gender' => null,
            'sexual_orientation' => null,
            'nationality' => null,
            'civil_status' => null,
            'religion' => null,
            'government_id_type' => null,
            'government_id_number' => null,
            'photo_path' => null,
            'photo_id' => null,
            'email' => null,
            'phone' => null,
            'mobile_number' => null,
            'landline_number' => null,
            'unit_number' => null,
            'building_name' => null,
            'street' => null,
            'subdivision' => null,
            'barangay' => null,
            'city_municipality' => null,
            'province' => null,
            'region' => null,
            'country' => null,
            'country_code' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'zip_code' => null,
            // 'archived' is the students.status enum's terminal value — marks the
            // skeleton as retired without needing a schema change.
            'status' => 'archived',
        ])->save();
    }

    /** Delete the applicant's login only when it is a plain, unshared student account. */
    private function maybeDeleteUser(int $userId): bool
    {
        $user = User::find($userId);

        if (! $user || $user->role !== 'student') {
            return false;
        }

        // The student row is already deleted in this transaction; if another
        // student still points at this user, keep the login.
        if (Student::where('user_id', $userId)->exists()) {
            return false;
        }

        // Never orphan a parent-portal relationship.
        if (DB::table('parent_student')->where('parent_user_id', $userId)->exists()) {
            return false;
        }

        $user->delete();

        return true;
    }
}
