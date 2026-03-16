<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('superadmin.profile.edit', [
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20', // Added validation for your admin mobile number
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Explicitly updating the phone field alongside name and email
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone; 

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Master profile updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2FA SETUP LOGIC (Inside Profile)
    |--------------------------------------------------------------------------
    */

    public function enable2FA(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey();
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'LMS Engine', 
            $user->email,
            $secret
        );

        session(['2fa_secret' => $secret, '2fa_url' => $qrCodeUrl]);

        return back()->with('show_2fa_modal', true);
    }

    public function confirm2FA(Request $request)
    {
        $request->validate(['one_time_password' => 'required']);
        
        $google2fa = app('pragmarx.google2fa');
        $secret = session('2fa_secret');

        $valid = $google2fa->verifyKey($secret, $request->one_time_password);

        if ($valid) {
            $user = auth()->user();
            $user->google2fa_secret = $secret;

            $codes = collect(range(1, 8))->map(function () {
                return strtoupper(Str::random(10) . '-' . Str::random(5));
            });
            
            $user->recovery_codes = encrypt($codes->toJson());
            $user->save();
            
            session()->forget(['2fa_secret', '2fa_url']);
            session(['2fa_verified' => true]); 

            return redirect()->route('superadmin.profile.edit')
                ->with('success', 'Security Shield fully activated.')
                ->with('recovery_codes', $codes); 
        }

        return back()->with('show_2fa_modal', true)->withErrors(['otp' => 'Invalid verification code.']);
    }

    public function disable2FA()
    {
        $user = auth()->user();
        $user->google2fa_secret = null;
        $user->recovery_codes = null; 
        $user->save();

        session()->forget('2fa_verified');

        return back()->with('success', 'Security Shield has been deactivated.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2FA CHALLENGE & RECOVERY LOGIC (During Login)
    |--------------------------------------------------------------------------
    */

    public function show2FAVerify()
    {
        return view('auth.2fa-verify');
    }

    public function post2FAVerify(Request $request)
    {
        $request->validate(['one_time_password' => 'required']);

        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            session(['2fa_verified' => true]);
            return redirect()->route('superadmin.dashboard');
        }

        return back()->withErrors(['otp' => 'The security code is incorrect.']);
    }

    public function showRecovery()
    {
        return view('auth.2fa-recovery');
    }

    public function postRecovery(Request $request)
    {
        $request->validate(['recovery_key' => 'required|string']);
        
        $user = auth()->user();
        $recoveryCodes = collect(json_decode(decrypt($user->recovery_codes), true));

        if ($recoveryCodes->contains(strtoupper($request->recovery_key))) {
            
            $newCodes = $recoveryCodes->reject(fn($code) => $code === strtoupper($request->recovery_key));
            $user->recovery_codes = encrypt($newCodes->values()->toJson());
            $user->save();

            session(['2fa_verified' => true]);
            return redirect()->route('superadmin.dashboard')
                ->with('success', 'Access authorized via Recovery Key.');
        }

        return back()->withErrors(['key' => 'Invalid or already used recovery key.']);
    }
}