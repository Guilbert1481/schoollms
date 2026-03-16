<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\School;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm(Request $request, $slug = null)
    {
        $school = $slug ? School::where('slug', $slug)->first() : null;

        return view('auth.login', compact('school'));
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

        // 1. Identify the intended School if a slug is present
        $school = null;
        if ($slug) {
            $school = School::where('slug', $slug)->first();

            if (!$school) {
                return back()->withErrors([
                    'email' => 'Invalid institution link.'
                ]);
            }

            // Add school_id to credentials so Auth::attempt only finds users for THIS school
            $credentials['school_id'] = $school->id;
        }

        // 2. Attempt Authentication with scoped credentials
        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            
            // FALLBACK: Allow Superadmins to bypass school_id check on school-specific pages
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
     * Handle redirects and status checks after successful Auth::attempt
     */
    protected function handlePostLogin(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | School Approval & Activation Protection
        |--------------------------------------------------------------------------
        */
        if ($user->role !== 'superadmin' && $user->school) {

            if ($user->school->approval_status !== 'approved') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your institution is pending approval by the platform administrator.'
                ]);
            }

            if (!$user->school->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This institution is currently inactive.'
                ]);
            }
        }

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Global Dashboard Redirect
        |--------------------------------------------------------------------------
        */
        return redirect()->route('dashboard');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}