<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Deadline;
use App\Models\ChatThread;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CommunicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $latestAnnouncements = Announcement::latest()
            ->take(5)
            ->get();

        $upcomingDeadlines = Deadline::whereDate('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $overdueDeadlines = Deadline::whereDate('due_date', '<', now())
            ->get();

        $chatThreads = ChatThread::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->latest()->take(5)->get();

        return view('communication.index', compact(
            'latestAnnouncements',
            'upcomingDeadlines',
            'overdueDeadlines',
            'chatThreads'
        ));
    }
}
