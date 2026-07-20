<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Role-agnostic 2FA flows (Roadmap M2): mandatory enrolment for staff roles,
 * the once-per-session login challenge, and recovery-key fallback. The
 * superadmin profile keeps its own enable/disable UI; this controller is the
 * one the TwoFactorMiddleware gate funnels everyone through.
 */
class TwoFactorController extends Controller
{
    /** Show the enrolment page (secret held in session until confirmed). */
    public function setup(Request $request)
    {
        $user = $request->user();

        // Already enrolled → this page is not for you; go verify instead.
        if ($user->google2fa_secret) {
            return redirect()->route('2fa.verify');
        }

        $google2fa = app('pragmarx.google2fa');

        $secret = session('2fa_setup_secret');
        if (! $secret) {
            $secret = $google2fa->generateSecretKey();
            session(['2fa_setup_secret' => $secret]);
        }

        $otpauthUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Sophentis'),
            $user->email,
            $secret
        );

        return view('auth.2fa-setup', [
            'secret' => $secret,
            'otpauthUrl' => $otpauthUrl,
        ]);
    }

    /** Confirm enrolment with a first valid code; issue recovery keys once. */
    public function confirmSetup(Request $request)
    {
        $request->validate(['one_time_password' => 'required|string']);

        $user = $request->user();
        $secret = session('2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('2fa.setup');
        }

        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey($secret, $request->one_time_password)) {
            return back()->withErrors(['otp' => 'That code did not match — check your authenticator app and try again.']);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => strtoupper(Str::random(10)))
            ->values();

        $user->google2fa_secret = $secret;
        $user->recovery_codes = encrypt($recoveryCodes->toJson());
        $user->save();

        session()->forget('2fa_setup_secret');
        session(['2fa_verified' => true]);

        // Shown exactly once — the codes are stored encrypted and never
        // redisplayed.
        return view('auth.2fa-recovery-codes', ['codes' => $recoveryCodes]);
    }

    /** The once-per-session login challenge. */
    public function challenge(Request $request)
    {
        if (! $request->user()->google2fa_secret) {
            return redirect()->route('dashboard');
        }

        return view('auth.2fa-verify');
    }

    public function verify(Request $request)
    {
        $request->validate(['one_time_password' => 'required|string']);

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey((string) $user->google2fa_secret, $request->one_time_password)) {
            return back()->withErrors(['otp' => 'The security code is incorrect.']);
        }

        session(['2fa_verified' => true]);

        return redirect()->intended(route('dashboard'));
    }

    public function recovery()
    {
        return view('auth.2fa-recovery');
    }

    /** Burn one recovery key to pass the challenge (single-use). */
    public function useRecovery(Request $request)
    {
        $request->validate(['recovery_key' => 'required|string']);

        $user = $request->user();

        if (! $user->recovery_codes) {
            return back()->withErrors(['key' => 'No recovery keys are on file for this account.']);
        }

        $codes = collect(json_decode(decrypt($user->recovery_codes), true));
        $offered = strtoupper(trim($request->recovery_key));

        if (! $codes->contains($offered)) {
            return back()->withErrors(['key' => 'Invalid or already used recovery key.']);
        }

        $user->recovery_codes = encrypt($codes->reject(fn ($c) => $c === $offered)->values()->toJson());
        $user->save();

        session(['2fa_verified' => true]);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Access authorized via recovery key. Consider re-enrolling your authenticator.');
    }
}
