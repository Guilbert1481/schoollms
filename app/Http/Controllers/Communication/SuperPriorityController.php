<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperPriorityController extends Controller
{   
    /**
     * Handle the creation of a Super Priority message.
     * This bypasses the regular AnnouncementController to ensure 
     * the 1-hour rule is strictly enforced.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Using "new" and "save" to bypass any potential $fillable issues
        $announcement = new Announcement();
        $announcement->title = $validated['title'];
        $announcement->content = $validated['content'];
        $announcement->created_by = Auth::id();
        $announcement->school_id = Auth::user()->school_id;
        $announcement->published_at = now();
        $announcement->priority_level = 'super';
        
        // 🕒 THE 1-HOUR RULE
        // This ensures the column is NOT null in the database.
        $announcement->super_priority_expires_at = now()->addHour();
        
        // Also fill expires_at so it passes regular 'active' filters
        $announcement->expires_at = now()->addHour(); 

        $announcement->save();

        return redirect()->route('communication.announcements.index')
            ->with('success', '🚨 SUPER PRIORITY message is now LIVE for 1 hour.');
    }

    /**
     * Handle the Acknowledge button click.
     * This marks the message as read for the user so they see the Quote again.
     */
    public function acknowledge($id)
    {
        $announcement = Announcement::findOrFail($id);

        // Check if the relationship name in your Model is 'acknowledgements' or 'acknowledgments'
        // If your ServiceProvider uses 'acknowledgements' (with 'e'), use that here too.
        AnnouncementAcknowledgement::firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id'         => Auth::id(),
            ],
            [
                'school_id'       => Auth::user()->school_id,
                'acknowledged_at' => now(),
            ]
        );

        return back();
    }
}