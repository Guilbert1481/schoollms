<?php

namespace App\Http\Controllers\Staff\Registrar\Settings;

use App\Http\Controllers\Controller;
use App\Models\ParentProvisioningIssue;
use App\Models\ParentStudentLink;
use App\Models\RegistrarSetting;
use App\Services\ParentPortal\ParentProvisioningService;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar → Settings → Parent Portal: the auto-provisioning kill-switch,
 * every parent↔child access link for this school (enable / disable /
 * re-issue credentials), and the provisioning issues that need a human
 * (no guardian email, email collisions, failed credential sends).
 */
class ParentPortalSettingController extends Controller
{
    public function index()
    {
        $schoolId = $this->schoolIdOrAbort();

        $setting = RegistrarSetting::forSchool($schoolId);

        // Every access link whose CHILD belongs to this school (parents are
        // cross-school; the registrar only manages their own school's links).
        $links = DB::table('parent_student as ps')
            ->join('students as s', 's.id', '=', 'ps.student_id')
            ->join('users as pu', 'pu.id', '=', 'ps.parent_user_id')
            ->where('s.school_id', $schoolId)
            ->orderBy('pu.last_name')->orderBy('pu.first_name')->orderBy('s.last_name')
            ->get([
                'ps.id',
                'ps.relationship',
                'ps.access_status',
                'ps.created_at',
                'pu.id as parent_id',
                'pu.first_name as parent_first_name',
                'pu.last_name as parent_last_name',
                'pu.email as parent_email',
                'pu.credentials_sent_at',
                'pu.password_change_required',
                's.id as student_id',
                's.first_name as student_first_name',
                's.last_name as student_last_name',
                's.student_number',
            ]);

        $issues = DB::table('parent_provisioning_issues as i')
            ->join('students as s', 's.id', '=', 'i.student_id')
            ->where('i.school_id', $schoolId)
            ->whereNull('i.resolved_at')
            ->orderByDesc('i.id')
            ->get([
                'i.id',
                'i.issue',
                'i.email',
                'i.details',
                'i.created_at',
                's.first_name as student_first_name',
                's.last_name as student_last_name',
                's.student_number',
            ]);

        return view('registrar.settings.parent_portal', [
            'setting'     => $setting,
            'links'       => $links,
            'issues'      => $issues,
            'issueLabels' => ParentProvisioningIssue::LABELS,
        ]);
    }

    public function update(Request $request)
    {
        $schoolId = $this->schoolIdOrAbort();

        RegistrarSetting::forSchool($schoolId)->update([
            'parent_portal_auto_provision' => $request->boolean('parent_portal_auto_provision'),
        ]);

        return back()->with('success', 'Parent portal settings saved.');
    }

    /** Disable / re-enable one parent↔child access link. */
    public function toggleLink(ParentStudentLink $link)
    {
        $this->authorizeLink($link);

        $link->update([
            'access_status' => $link->isActive()
                ? ParentStudentLink::STATUS_DISABLED
                : ParentStudentLink::STATUS_ACTIVE,
        ]);

        return back()->with('success', $link->isActive()
            ? 'Parent access re-enabled.'
            : 'Parent access disabled.');
    }

    /**
     * Regenerate the one-time password and resend the credential email.
     * NOTE: this resets the parent's password portal-wide (their account
     * spans schools) — the confirmation text in the view says so.
     */
    public function reissueLink(ParentStudentLink $link, ParentProvisioningService $provisioning)
    {
        $this->authorizeLink($link);

        $parent  = User::findOrFail((int) $link->parent_user_id);
        $student = Student::findOrFail((int) $link->student_id);

        $sent = $provisioning->reissueCredentials($parent, $student, (int) auth()->id());

        return $sent
            ? back()->with('success', 'Credentials re-issued and emailed to '.$parent->email.'.')
            : back()->with('error', 'Credentials were reset but the email failed to send — check the school SMTP settings and try again.');
    }

    /** Dismiss a provisioning issue the registrar has dealt with outside the system. */
    public function resolveIssue(ParentProvisioningIssue $issue)
    {
        abort_unless((int) $issue->school_id === $this->schoolIdOrAbort(), 403);

        $issue->update(['resolved_at' => now()]);

        return back()->with('success', 'Issue marked resolved.');
    }

    protected function schoolIdOrAbort(): int
    {
        $schoolId = (int) (auth()->user()->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'No school assigned.');

        return $schoolId;
    }

    /** The link's CHILD must belong to the registrar's school. */
    protected function authorizeLink(ParentStudentLink $link): void
    {
        $childSchoolId = (int) DB::table('students')
            ->where('id', (int) $link->student_id)
            ->value('school_id');

        abort_unless($childSchoolId === $this->schoolIdOrAbort(), 403);
    }
}
