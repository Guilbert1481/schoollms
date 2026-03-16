<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatMessage;
use App\Models\Quote;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Global School Data
        |--------------------------------------------------------------------------
        */
        View::composer('*', function ($view) {
            $school = DB::table('schools')->first();

            $view->with([
                'school_name' => $school->school_name ?? 'Memory Ridge International Schools',
                'school_logo' => $school->school_logo ?? null,
            ]);
        });


       /*
        |--------------------------------------------------------------------------
        | Global Header Content (Quote or Super Priority)
        |--------------------------------------------------------------------------
        */
        View::composer('*', function ($view) {

            if (!auth()->check()) {
                $view->with([
                    'globalQuote' => null,
                    'superPriority' => null,
                    'superPriorityCount' => 0,
                ]);
                return;
            }

            // 🔴 Check active Super Priority (not expired, not acknowledged)
            $superPriorityQuery = \App\Models\Announcement::with('creator')
                ->where('priority_level', 'super')
                ->where('super_priority_expires_at', '>', now())
                ->whereDoesntHave('acknowledgements', function ($q) {
                    $q->where('user_id', auth()->id());
                });

            $superPriority = $superPriorityQuery->first();
            $superPriorityCount = $superPriorityQuery->count();

            // 🟢 Normal Daily Quote (only if no super priority)
            $globalQuote = null;

            if (!$superPriority) {
                $globalQuote = \App\Models\Quote::where('is_active', true)
                    ->orderBy('activated_at', 'desc')
                    ->first();
            }

            $view->with([
                'globalQuote' => $globalQuote,
                'superPriority' => $superPriority,
                'superPriorityCount' => $superPriorityCount,
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Sidebar Unread Counter (for communication views only)
        |--------------------------------------------------------------------------
        */
        View::composer('*', function ($view) {

            $unreadChats = 0;
            $unreadAnnouncements = 0;
            $unreadDeadlines = 0;

            if (Auth::check()) {

                $user = Auth::user();

                $threads = $user->chatThreads()
                    ->withPivot('last_read_at')
                    ->get();

                foreach ($threads as $thread) {

                    $lastRead = $thread->pivot->last_read_at ?? '1970-01-01';

                    $unreadChats += ChatMessage::where('chat_thread_id', $thread->id)
                        ->where('created_at', '>', $lastRead)
                        ->where('user_id', '!=', $user->id)
                        ->count();
                }
            }

            $view->with(compact(
                'unreadChats',
                'unreadAnnouncements',
                'unreadDeadlines'
            ));
        });


        /*
        |--------------------------------------------------------------------------
        | User-Specific Theme Settings
        |--------------------------------------------------------------------------
        */
        
    }
}