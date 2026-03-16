<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($request->file('profile_photo'));
        $image->cover(300, 300);
        $filename = uniqid() . '.webp';
        Storage::disk('public')->put(
            'profile_photos/' . $filename,
            (string) $image->toWebp(80)
        );
        if (auth()->user()->profile_photo) {
            Storage::disk('public')->delete(auth()->user()->profile_photo);
        }
        auth()->user()->update([
            'profile_photo' => 'profile_photos/' . $filename
        ]);
        return back()->with('success', 'Profile photo updated.');
    }
}
