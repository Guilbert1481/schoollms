<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the unified profile settings page.
     */
    public function index()
    {
        $user     = Auth::user();
        $settings = $this->loadSystemSettings($user);

        return view('settings.profile', [
            'user'     => $user,
            'settings' => $settings,
            'isAdmin'  => in_array($user->role, ['admin', 'superadmin', 'admission_manager'], true),
        ]);
    }

    /**
     * Update personal info (name, email, phone, photo).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'         => 'nullable|string|max:32',
            'profile_photo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('profile_photo')) {
            // Delete previous photo, if any.
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')
                ->store('profile_photos', 'public');
        } else {
            unset($data['profile_photo']);
        }

        // SECURITY: a parent-portal account's email is its provisioning-linked
        // identity — the parent_student auto-provisioner trusts that a
        // role=parent account only ever holds a guardian email a school
        // actually recorded (see ParentProvisioningService). Letting a parent
        // self-edit their email would break that trust anchor and allow
        // silently inheriting another family's child at the next gate clear.
        // Parents change their email through the school (registrar), not here.
        if ($user->isParent()) {
            unset($data['email']);
        }

        $user->fill($data)->save();

        return back()->with('status', 'Profile updated successfully.');
    }

    /**
     * Change password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = $request->password;
        // The user has now chosen their own password — release the one-time-
        // password gate (set when an account is auto-provisioned/re-issued).
        $user->password_change_required = false;
        $user->save();

        return back()->with('status', 'Password changed successfully.');
    }

    /**
     * Update SMTP (admin / superadmin only).
     */
    public function updateSmtp(Request $request)
    {
        $this->abortUnlessAdmin();

        $data = $request->validate([
            'smtp_host'         => 'nullable|string|max:191',
            'smtp_port'         => 'nullable|integer|min:1|max:65535',
            'smtp_username'     => 'nullable|string|max:191',
            'smtp_password'     => 'nullable|string|max:500',
            'smtp_encryption'   => 'nullable|in:tls,ssl,none',
            'smtp_from_address' => 'nullable|email|max:191',
            'smtp_from_name'    => 'nullable|string|max:191',
        ]);

        if (($data['smtp_encryption'] ?? null) === 'none') {
            $data['smtp_encryption'] = null;
        }

        $settings = $this->resolveOrCreateSettings();

        // If the password field is left blank, keep the existing value.
        if (empty($data['smtp_password'])) {
            unset($data['smtp_password']);
        }

        $settings->fill($data)->save();

        return back()->with('status', 'Email / SMTP settings saved.');
    }

    /**
     * Update SMS gateway (admin / superadmin only).
     */
    public function updateSms(Request $request)
    {
        $this->abortUnlessAdmin();

        $data = $request->validate([
            'sms_enabled'     => 'nullable|boolean',
            'sms_provider'    => 'nullable|string|max:64',
            'sms_api_key'     => 'nullable|string|max:500',
            'sms_api_secret'  => 'nullable|string|max:500',
            'sms_sender_name' => 'nullable|string|max:64',
            'sms_from_number' => 'nullable|string|max:32',
        ]);

        $data['sms_enabled'] = (bool) ($data['sms_enabled'] ?? false);

        $settings = $this->resolveOrCreateSettings();

        if (empty($data['sms_api_key']))    unset($data['sms_api_key']);
        if (empty($data['sms_api_secret'])) unset($data['sms_api_secret']);

        $settings->fill($data)->save();

        return back()->with('status', 'SMS gateway settings saved.');
    }

    /**
     * Send a test email using the current SMTP settings.
     */
    public function sendTestEmail(Request $request)
    {
        $this->abortUnlessAdmin();

        $request->validate(['to' => 'required|email']);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from your SchoolLMS profile settings. If you received this, SMTP is configured correctly.',
                function ($m) use ($request) {
                    $m->to($request->to)->subject('SchoolLMS — SMTP test');
                }
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['smtp_test' => 'Failed to send: '.$e->getMessage()]);
        }

        return back()->with('status', 'Test email sent to '.$request->to);
    }

    /* ---------- helpers ---------- */

    protected function abortUnlessAdmin(): void
    {
        $u = Auth::user();
        abort_unless(in_array($u?->role, ['admin', 'superadmin', 'admission_manager'], true), 403);
    }

    protected function loadSystemSettings($user): ?SystemSetting
    {
        if (! $user) return null;

        return SystemSetting::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->first();
    }

    protected function resolveOrCreateSettings(): SystemSetting
    {
        $user = Auth::user();

        return SystemSetting::firstOrCreate(
            ['school_id' => $user->school_id],
            []
        );
    }
}
