<?php

namespace App\Http\Controllers\GuidanceCounselor;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Guidance Counselor — Flagged Interventions.
 *
 * Surfaces every flagged communication (chat / deadlines / announcements)
 * the system has captured so the guidance team can review and intervene.
 *
 * For the MVP only chat_messages.is_flagged is wired in; deadline and
 * announcement sources are stubbed via aggregateFlagged() so adding new
 * sources later is just one method call.
 */
class FlaggedInterventionsController extends Controller
{
    public function index(Request $request)
    {
        $level  = $request->query('level', 'all'); // all | warning | critical
        $search = trim((string) $request->query('q', ''));

        $items = $this->aggregateFlagged($level, $search);

        return view('guidance_counselor.flagged.index', [
            'items'        => $items,
            'currentLevel' => $level,
            'search'       => $search,
        ]);
    }

    /**
     * Return a single flagged item's full payload (used by the modal).
     */
    public function show(string $source, int $id)
    {
        if ($source !== 'chat') {
            abort(404);
        }

        $msg = ChatMessage::with(['user', 'thread'])->where('is_flagged', true)->findOrFail($id);

        return response()->json([
            'id'          => $msg->id,
            'source'      => 'chat',
            'level'       => $msg->flag_level,
            'message'     => $msg->message,
            'created_at'  => optional($msg->created_at)->toIso8601String(),
            'sent_date'   => optional($msg->created_at)->format('M d, Y'),
            'sent_time'   => optional($msg->created_at)->format('h:i A'),
            'created_by'  => $this->displayName($msg->user),
            'thread_id'   => $msg->chat_thread_id,
        ]);
    }

    /**
     * Pull and normalize every flagged communication source.
     */
    protected function aggregateFlagged(string $level, string $search): Collection
    {
        $rows = collect();

        // ---- 1. Chat messages -------------------------------------------------
        $chat = ChatMessage::with('user')
            ->where('is_flagged', true)
            ->when($level !== 'all', fn ($q) => $q->where('flag_level', $level))
            ->when($search !== '', fn ($q) => $q->where('message', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn (ChatMessage $m) => [
                'id'          => $m->id,
                'source'      => 'chat',
                'source_label'=> 'Chat',
                'level'       => $m->flag_level,
                'message'     => (string) $m->message,
                'preview'     => $this->preview($m->message),
                'sent_date'   => optional($m->created_at)->format('M d, Y'),
                'sent_time'   => optional($m->created_at)->format('h:i A'),
                'created_by'  => $this->displayName($m->user),
                'created_at'  => $m->created_at,
            ]);

        $rows = $rows->concat($chat);

        // ---- 2. Deadlines / Announcements ------------------------------------
        // These tables don't yet carry an is_flagged column. Once they do,
        // add another section here that follows the same shape and concat.

        return $rows->sortByDesc('created_at')->values();
    }

    protected function preview(?string $text, int $length = 80): string
    {
        $clean = trim((string) $text);

        if ($clean === '') {
            return '(no content)';
        }

        return mb_strlen($clean) > $length
            ? mb_substr($clean, 0, $length) . '…'
            : $clean;
    }

    protected function displayName($user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $full = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $full !== '' ? $full : ($user->name ?? $user->email ?? 'User #' . $user->id);
    }
}
