<?php

namespace App\Services\ParentPortal;

use App\Mail\ParentPortalCredentialsMail;
use App\Models\FinanceSetting;
use App\Models\ParentProvisioningIssue;
use App\Models\ParentStudentLink;
use App\Models\RegistrarSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Auto-provisions parent portal accounts when the registrar clears an
 * enrollment (assessed / provisional) — the operator's chosen trigger.
 *
 * Rules:
 *  - Per-school kill-switch: registrar_settings.parent_portal_auto_provision.
 *  - The primary guardian email (type parent/guardian, never the emergency
 *    contact) becomes the account email.
 *  - Find-or-create by email: a second child (or a re-run of the gate action)
 *    attaches to the EXISTING parent account as a new link — no duplicates,
 *    idempotent.
 *  - An email owned by a NON-parent user is never merged; it is surfaced to
 *    the registrar as an email_collision issue instead.
 *  - The credential email carries a one-time password; sending failures never
 *    block provisioning (they become a send_failed issue for re-issue).
 */
class ParentProvisioningService
{
    public function provisionForEnrollment(StudentEnrollment $enrollment, ?int $actorId = null): void
    {
        // Explicit fetch (not the relation property): StudentEnrollment's
        // relations lack return types, which trips Larastan — house pattern.
        $student  = Student::find((int) $enrollment->student_id);
        $schoolId = (int) $enrollment->school_id;

        if (! $student || ! $schoolId) {
            return;
        }

        if (! RegistrarSetting::forSchool($schoolId)->parent_portal_auto_provision) {
            return;
        }

        $guardian = $this->bestGuardian($student);
        if (! $guardian) {
            $this->recordIssue($schoolId, $student, $enrollment->id, ParentProvisioningIssue::ISSUE_NO_EMAIL);

            return;
        }

        $email    = strtolower(trim((string) $guardian->email));
        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing && $existing->role !== 'parent') {
            $this->recordIssue(
                $schoolId,
                $student,
                $enrollment->id,
                ParentProvisioningIssue::ISSUE_EMAIL_COLLISION,
                $email,
                'The guardian email already belongs to a '.($existing->role ?: 'user').' account (user #'.$existing->id.').'
            );

            return;
        }

        $temporaryPassword = null;
        if ($existing) {
            // Adopt the existing parent account (same guardian, another child).
            // SECURITY: this trusts that a role=parent account only ever holds
            // an email a school recorded on a guardian record — which holds
            // because parents cannot self-edit their email
            // (ProfileController::update) and no other UI mutates it. If you
            // ever add a path that lets a parent's email change to an arbitrary
            // value, you MUST re-verify ownership here, or an attacker who
            // squats a not-yet-provisioned guardian email would silently
            // inherit that child at the next gate clear.
            $parent = $existing;
        } else {
            $temporaryPassword = Str::random(12);
            $parent = User::create([
                'first_name'  => $guardian->first_name ?: 'Parent',
                'middle_name' => $guardian->middle_name,
                'last_name'   => $guardian->last_name ?: ($student->last_name ?: 'Guardian'),
                'email'       => $email,
                'password'    => $temporaryPassword, // hashed by the model cast
                'role'        => 'parent',
                'school_id'   => null, // cross-school: scoping runs through parent_student links
                'phone'       => $guardian->mobile_number,
            ]);
            $parent->password_change_required = true;
            $parent->save();
        }

        ParentStudentLink::firstOrCreate(
            [
                'parent_user_id' => $parent->id,
                'student_id'     => $student->id,
            ],
            [
                'relationship'  => $guardian->relationship ?: $guardian->type,
                'is_primary'    => (bool) $guardian->is_primary,
                'access_status' => ParentStudentLink::STATUS_ACTIVE,
                'created_by'    => $actorId,
            ]
        );

        // Provisioning succeeded — clear stale account-creation issues.
        $this->resolveIssues($student->id, [
            ParentProvisioningIssue::ISSUE_NO_EMAIL,
            ParentProvisioningIssue::ISSUE_EMAIL_COLLISION,
        ]);

        // Credentials go out only for a NEWLY created account; an existing
        // parent already has working credentials and simply gains the child.
        if ($temporaryPassword !== null) {
            $this->deliverCredentials($parent, $temporaryPassword, $student, $schoolId, $enrollment->id);
        }
    }

    /**
     * Registrar action: regenerate the one-time password and resend the
     * credential email. Returns true when the email actually went out.
     */
    public function reissueCredentials(User $parent, Student $student, ?int $actorId = null): bool
    {
        if ($parent->role !== 'parent') {
            return false;
        }

        $temporaryPassword = Str::random(12);
        $parent->password = $temporaryPassword; // hashed by the model cast
        $parent->password_change_required = true;
        $parent->save();

        return $this->deliverCredentials($parent, $temporaryPassword, $student, (int) $student->school_id, null);
    }

    /**
     * The guardian whose email becomes the parent account: primary first,
     * parent/guardian types only (never the emergency contact), and the email
     * must actually be valid.
     */
    protected function bestGuardian(Student $student): ?object
    {
        return $student->guardians()
            ->whereIn('type', ['parent', 'guardian'])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->first(fn ($g) => filter_var($g->email ?? null, FILTER_VALIDATE_EMAIL));
    }

    /** Send the credential mail; failures become a send_failed issue, never an exception. */
    protected function deliverCredentials(
        User $parent,
        string $temporaryPassword,
        Student $student,
        int $schoolId,
        ?int $enrollmentId
    ): bool {
        $setting     = FinanceSetting::forSchool($schoolId);
        $studentName = trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
        $schoolName  = (string) (\App\Models\School::query()->whereKey($schoolId)->value('school_name') ?: 'School');

        try {
            Mail::mailer($this->mailerFor($setting))
                ->to($parent->email)
                ->send(new ParentPortalCredentialsMail($parent, $temporaryPassword, $studentName, $schoolName, $setting));

            $parent->credentials_sent_at = now();
            $parent->save();

            $this->resolveIssues($student->id, [ParentProvisioningIssue::ISSUE_SEND_FAILED]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Parent portal credential mail failed', [
                'parent_user_id' => $parent->id,
                'student_id'     => $student->id,
                'error'          => $e->getMessage(),
            ]);

            $this->recordIssue(
                $schoolId,
                $student,
                $enrollmentId,
                ParentProvisioningIssue::ISSUE_SEND_FAILED,
                $parent->email,
                Str::limit($e->getMessage(), 300)
            );

            return false;
        }
    }

    /** One unresolved row per (student, issue) — updated in place, never duplicated. */
    protected function recordIssue(
        int $schoolId,
        Student $student,
        ?int $enrollmentId,
        string $issue,
        ?string $email = null,
        ?string $details = null
    ): void {
        $row = ParentProvisioningIssue::query()
            ->where('student_id', $student->id)
            ->where('issue', $issue)
            ->whereNull('resolved_at')
            ->first() ?? new ParentProvisioningIssue([
                'student_id' => $student->id,
                'issue'      => $issue,
            ]);

        $row->school_id             = $schoolId;
        $row->student_enrollment_id = $enrollmentId;
        $row->email                 = $email;
        $row->details               = $details;
        $row->save();
    }

    protected function resolveIssues(int $studentId, array $issues): void
    {
        ParentProvisioningIssue::query()
            ->where('student_id', $studentId)
            ->whereIn('issue', $issues)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    /**
     * Resolve the mailer to send through — the school's finance SMTP when
     * configured, otherwise the application default (same pattern as
     * FinanceMailService::mailerFor).
     */
    protected function mailerFor(FinanceSetting $setting): string
    {
        if (! $setting->hasFinanceSmtp()) {
            return (string) config('mail.default', 'smtp');
        }

        $name = 'finance_smtp_'.$setting->school_id;
        config(["mail.mailers.{$name}" => [
            'transport'  => 'smtp',
            'host'       => $setting->smtp_host,
            'port'       => (int) ($setting->smtp_port ?: 587),
            'username'   => $setting->smtp_username,
            'password'   => $setting->smtp_password,
            'encryption' => $setting->smtp_encryption ?: null,
            'timeout'    => null,
        ]]);
        Mail::purge($name);

        return $name;
    }
}
