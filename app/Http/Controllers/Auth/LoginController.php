<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm(Request $request, $slug = null)
    {
        // If the user was just kicked out because their school was deactivated,
        // bounce them to that school's public website with a notice — even if
        // the original kick was an AJAX response that they didn't see.
        if ($request->cookie('school_inactive_slug')) {
            $inactiveSlug = $request->cookie('school_inactive_slug');
            $notice       = $request->cookie('school_inactive_notice')
                ?: 'Your institution has been deactivated.';

            if ($inactiveSlug && School::where('slug', $inactiveSlug)->exists()) {
                return redirect()
                    ->route('website.home', ['schoolSlug' => $inactiveSlug])
                    ->with('warning', $notice)
                    ->with('school_inactive_notice', $notice)
                    ->withCookie(cookie()->forget('school_inactive_slug'))
                    ->withCookie(cookie()->forget('school_inactive_notice'));
            }
        }

        $school = $slug ? School::where('slug', $slug)->first() : null;

        $schoolProfile = $school
            ? \App\Models\SchoolProfile::where('school_id', $school->id)->first()
            : null;

        return view('auth.login', compact('school', 'schoolProfile'));
    }

    /**
     * Handle login
     */
    public function login(Request $request, $slug = null)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Identify school if slug exists
        $school = null;
        if ($slug) {
            $school = School::where('slug', $slug)->first();

            if (!$school) {
                return back()->withErrors([
                    'email' => 'Invalid institution link.'
                ]);
            }

            $credentials['school_id'] = $school->id;
        }

        // Attempt login
        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            
            // Superadmin fallback
            $userCheck = User::where('email', $request->email)->first();
            if ($userCheck && $userCheck->role === 'superadmin') {
                if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
                    return $this->handlePostLogin($request);
                }
            }

            return back()->withErrors([
                'email' => 'These credentials do not match our records for this institution.'
            ]);
        }

        return $this->handlePostLogin($request);
    }

    /**
     * After login logic
     */
    protected function handlePostLogin(Request $request)
    {
        $user = Auth::user();

        /*
        |------------------------------------------------------------------
        | School Approval & Activation Protection
        |------------------------------------------------------------------
        */
        if ($user->role !== 'superadmin' && $user->school) {

            if ($user->school->approval_status !== 'approved') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your institution is pending approval by the platform administrator.'
                ]);
            }

            if (!$user->school->is_active) {
                $slug = $user->school->slug;
                Auth::logout();

                $message = 'This institution is currently inactive.';

                if ($slug) {
                    return redirect()
                        ->route('website.home', ['schoolSlug' => $slug])
                        ->with('warning', $message);
                }

                return back()->withErrors(['email' => $message]);
            }
        }

        $request->session()->regenerate();

        /*
        |------------------------------------------------------------------
        | LOAD ACTIVE ACCOUNT ACCESS
        |------------------------------------------------------------------
        */
        $access = DB::table('account_access')
            ->where('user_id', $user->id)
            ->where('is_active', 1)
            ->first();

        if ($access) {
            $request->session()->put([
                'active_access_id'  => $access->id,
                'active_profile_id' => $access->person_id,
                'active_role_id'    => $access->role_id,
            ]);
        }

        /*
        |------------------------------------------------------------------
        | Redirect to dashboard
        |------------------------------------------------------------------
        */
        $response = redirect()->route('dashboard');

        // Remember the school slug for ~1 year so that even after the
        // session expires (419) we can bounce the user back to the
        // school's website instead of the global login page.
        if ($user->role !== 'superadmin' && $user->school && $user->school->slug) {
            $response->withCookie(cookie('last_school_slug', $user->school->slug, 60 * 24 * 365));
        }

        return $response;
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $slug = null;

        if ($user && $user->role !== 'superadmin' && $user->school) {
            $slug = $user->school->slug;
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($slug) {
            return redirect()->route('website.home', ['schoolSlug' => $slug]);
        }

        return redirect()->route('login');
    }
}