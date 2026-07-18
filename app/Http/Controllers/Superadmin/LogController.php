<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;

/**
 * Superadmin → Logs. Read-only viewers over the append-only log tables
 * (Roadmap Phase 4). No clear/delete actions by design: the logs' value is
 * that nobody — superadmin included — can rewrite history from the UI.
 */
class LogController extends Controller
{
    /** Login success/failure activity, filterable by outcome and email/IP. */
    public function logins(Request $request)
    {
        $event = in_array($request->query('event'), [LoginLog::EVENT_SUCCESS, LoginLog::EVENT_FAILED], true)
            ? $request->query('event')
            : null;
        $search = trim((string) $request->query('q'));

        $logs = LoginLog::query()
            ->with(['school:id,school_name'])
            ->when($event, fn ($q) => $q->where('event', $event))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('email', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('superadmin.logs.logins', [
            'logs' => $logs,
            'event' => $event,
            'search' => $search,
        ]);
    }
}
