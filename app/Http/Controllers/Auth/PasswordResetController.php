<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Self-service password reset (M1 — see MODERNIZATION_ROADMAP.md).
 *
 * Uses Laravel's built-in password broker + the `password_reset_tokens` table.
 * Additive: it does not touch the custom LoginController or the login flow.
 */
class PasswordResetController extends Controller
{
    /**
     * Step 1 — show the "enter your email" form.
     *
     * Brands the page for the originating context: a school login passes
     * ?school=slug so that school's name shows; the global superadmin login
     * passes nothing so the platform name ("Sophentis") shows. A dedicated
     * `brand` variable is used because the global View::composer('*') in
     * AppServiceProvider would otherwise overwrite any `school_name` set here.
     */
    public function showLinkRequest(Request $request)
    {
        $school = $request->query('school')
            ? \App\Models\School::where('slug', $request->query('school'))->first()
            : null;

        return view('auth.forgot-password', [
            'brand'      => $school?->school_name ?: 'Sophentis',
            'schoolSlug' => $school?->slug,
        ]);
    }

    /** Step 2 — email the reset link (rate-limited at the route). */
    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always report the generic "sent" message to avoid email enumeration.
        // Preserve the originating school context so the confirmation stays branded.
        $slug = $request->input('school');

        return redirect()
            ->route('password.request', $slug ? ['school' => $slug] : [])
            ->with('status', __(Password::RESET_LINK_SENT));
    }

    /** Step 3 — show the "choose a new password" form for a valid token link. */
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Step 4 — verify the token and set the new password. */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
