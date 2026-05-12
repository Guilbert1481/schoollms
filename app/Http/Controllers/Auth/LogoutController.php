<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
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

        return redirect('/login');
    }
}
