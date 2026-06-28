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
        // Apply DB-stored SMTP settings to the runtime mail config so that
        // Mail::send() uses the school's configured SMTP credentials.
        $this->applyDynamicMailConfig();

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
                $activeQuotes = \App\Models\Quote::where('is_active', true)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($activeQuotes->isNotEmpty()) {
                    $anchor = $activeQuotes->first();
                    $duration = max(1, (int) ($anchor->display_duration ?? 1));

                    // Days elapsed since activation
                    $daysSince = $anchor->activated_at
                        ? (int) now()->startOfDay()->diffInDays(
                            \Illuminate\Support\Carbon::parse($anchor->activated_at)->startOfDay()
                        )
                        : 0;

                    // Rotate through the active quotes, one per day, wrapping by duration
                    $cycleLength = min($duration, $activeQuotes->count());
                    $index = $cycleLength > 0 ? ($daysSince % $cycleLength) : 0;

                    $globalQuote = $activeQuotes[$index] ?? $anchor;
                }
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

    /**
     * Override the default mail config with values from `system_settings`
     * (if any have been configured via the Profile Settings page). This lets
     * a school's admin point all outgoing email at their own SMTP server
     * without touching .env.
     */
    protected function applyDynamicMailConfig(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                return;
            }

            $row = DB::table('system_settings')
                ->whereNotNull('smtp_host')
                ->orderBy('id')
                ->first();

            if (! $row || empty($row->smtp_host)) {
                return;
            }

            config([
                'mail.default'                       => 'smtp',
                'mail.mailers.smtp.host'             => $row->smtp_host,
                'mail.mailers.smtp.port'             => $row->smtp_port ?: 587,
                'mail.mailers.smtp.username'         => $row->smtp_username,
                'mail.mailers.smtp.password'         => $row->smtp_password,
                'mail.mailers.smtp.encryption'       => $row->smtp_encryption ?: null,
                'mail.from.address'                  => $row->smtp_from_address
                    ?: config('mail.from.address'),
                'mail.from.name'                     => $row->smtp_from_name
                    ?: config('mail.from.name'),
            ]);
        } catch (\Throwable $e) {
            // Don't let a config error block app boot.
            \Illuminate\Support\Facades\Log::warning('Dynamic mail config failed: '.$e->getMessage());
        }
    }
}