<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Notifications\FlaggedChatNotification;
use App\Notifications\NewChatMessageNotification;
use App\Services\Reusable\AssignableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Resolve a human-readable display name for a user, preferring profile.first/last name.
     */
    protected function displayName(?User $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $profile = $user->relationLoaded('profile') ? $user->profile : $user->profile()->first();
        if ($profile) {
            $parts = array_filter([$profile->first_name, $profile->middle_name, $profile->last_name]);
            $name = trim(implode(' ', $parts));
            if ($name !== '') {
                return $name;
            }
        }

        return $user->name ?: $user->email ?: 'User #'.$user->id;
    }

    public function index()
    {
        $threads = auth()->user()
            ->chatThreads()
            ->with('participants.profile')
            ->latest()
            ->get();

        $unreadChats = 0;

        foreach ($threads as $thread) {
            $participant = $thread->participants
                ->firstWhere('id', auth()->id());

            $lastRead = $participant?->pivot?->last_read_at;

            $thread->unread_count = $thread->messages()
                ->when($lastRead, function ($query) use ($lastRead) {
                    $query->where('created_at', '>', $lastRead);
                })
                ->where('user_id', '!=', auth()->id())
                ->count();

            $unreadChats += $thread->unread_count;

            // Attach a display name for the "other" participant on private threads.
            $other = $thread->participants->firstWhere('id', '!=', auth()->id());
            $thread->other_name = $other ? $this->displayName($other) : null;
        }

        $groups = app(AssignableService::class)
            ->getGroups(auth()->user()->school_id)
            ->toArray();

        return view('communication.chat.index', compact('threads', 'unreadChats', 'groups'));
    }

    /**
     * Search users (by profile name) available to start a chat with.
     */
    public function searchUsers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $schoolId = auth()->user()->school_id;

        $users = User::query()
            ->where('id', '!=', auth()->id())
            ->where('school_id', $schoolId)
            ->with('profile')
            ->whereHas('profile', function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get()
            ->map(function ($user) {
                $profile = $user->profile;
                $fullName = $profile
                    ? trim(collect([$profile->first_name, $profile->middle_name, $profile->last_name])->filter()->implode(' '))
                    : $user->name;

                return [
                    'id' => $user->id,
                    'name' => $fullName ?: $user->name,
                    'email' => $user->email,
                ];
            });

        return response()->json($users);
    }

    /**
     * Find or create a 1:1 thread between the current user and the target user.
     */
    public function startDirect(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Cannot chat with yourself.');

        $meId = auth()->id();

        // Look for an existing private thread containing both users.
        $thread = ChatThread::where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $meId))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->first();

        if (! $thread) {
            $thread = ChatThread::create([
                'title' => null,
                'type' => 'private',
                'school_id' => auth()->user()->school_id,
                'created_by' => $meId,
                'status' => 'active',
            ]);
            $thread->participants()->attach([$meId, $user->id]);
        }

        return response()->json([
            'thread' => [
                'id' => $thread->id,
                'title' => $this->displayName($user),
                'type' => 'private',
                'updated_at' => optional($thread->updated_at)->diffForHumans() ?? 'just now',
                'unread_count' => 0,
                'initial' => strtoupper(substr($this->displayName($user) ?: '?', 0, 1)),
            ],
        ]);
    }

    /**
     * Return messages for a thread as JSON (for the right-pane loader).
     */
    public function threadMessages(ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $meId = auth()->id();

        // Load participants + their profiles and message authors + their profiles.
        $thread->load(['participants.profile', 'messages.user.profile']);

        $avatarFor = function ($user) {
            if (! $user) {
                return 'https://ui-avatars.com/api/?name=?&background=e0e7ff&color=4f46e5';
            }
            if ($user->profile_photo) {
                return asset('storage/'.$user->profile_photo);
            }
            $name = $this->displayName($user);

            return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=e0e7ff&color=4f46e5';
        };

        $messages = $thread->messages
            ->sortBy('created_at')
            ->values()
            ->map(function ($m) use ($meId, $thread, $avatarFor) {
                $seenBy = $thread->participants
                    ->filter(function ($p) use ($m, $meId) {
                        if ($p->id === $meId || $p->id === $m->user_id) {
                            return false;
                        }
                        $lastRead = $p->pivot->last_read_at ?? null;

                        return $lastRead && $lastRead >= $m->created_at;
                    })
                    ->map(function ($p) use ($avatarFor) {
                        return [
                            'id' => $p->id,
                            'name' => $this->displayName($p),
                            'avatar' => $avatarFor($p),
                        ];
                    })
                    ->values();

                return array_merge([
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'user_name' => $this->displayName($m->user),
                    'user_avatar' => $avatarFor($m->user),
                    'message' => $m->message,
                    'is_mine' => $m->user_id === $meId,
                    'created_at' => optional($m->created_at)->format('M d, Y h:i A'),
                    'seen_by' => $seenBy,
                ], $this->attachmentPayload($m));
            });

        $thread->participants()->updateExistingPivot($meId, [
            'last_read_at' => now(),
        ]);

        // Mark chat-message notifications for this thread as read
        auth()->user()
            ->unreadNotifications
            ->where('data.chat_thread_id', $thread->id)
            ->markAsRead();

        $otherUser = $thread->participants->firstWhere('id', '!=', $meId);

        return response()->json([
            'thread' => [
                'id' => $thread->id,
                'title' => $thread->title
                    ?? ($thread->type === 'private' && $otherUser ? $this->displayName($otherUser) : 'Group Conversation'),
                'type' => $thread->type,
            ],
            'messages' => $messages,
        ]);
    }

    public function show(ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $messages = $thread->messages()
            ->orderBy('created_at')
            ->get();

        // Mark related notifications as read
        auth()->user()
            ->unreadNotifications
            ->where('data.chat_thread_id', $thread->id)
            ->markAsRead();

        // Update pivot last_read_at
        $thread->participants()
            ->updateExistingPivot(auth()->id(), [
                'last_read_at' => now(),
            ]);

        return view('communication.chat.show', compact('thread', 'messages'));
    }

    public function storeMessage(Request $request, ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $request->validate([
            'message' => 'nullable|string',
            // Server-side type allow-list (H3): images + common documents only.
            // SVG/HTML/JS and executables are rejected — they render as active
            // content when opened inline and are a stored-XSS vector.
            'attachment' => [
                'nullable', 'file', 'max:20480', // 20 MB
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
            ],
        ]);

        $messageText = (string) $request->input('message', '');

        if (trim($messageText) === '' && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Message or attachment required.'], 422);
        }

        // 🔎 Tiered Flag Detection (text only)
        $levels = config('chat.levels');
        $isFlagged = false;
        $flagLevel = null;
        $messageLower = strtolower($messageText);

        foreach ($levels as $level => $words) {
            foreach ($words as $word) {
                if (str_contains($messageLower, strtolower($word))) {
                    $isFlagged = true;
                    $flagLevel = $level;
                    break 2;
                }
            }
        }

        // 📎 Handle attachment (stored publicly so it can be opened/downloaded;
        // auto-deleted after 24 hours by the chat:purge-attachments command).
        $attachmentData = [
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ext = strtolower($file->getClientOriginalExtension());

            // Re-encode images to strip embedded payloads (H3); other allowed
            // document types are stored as-is with a random path.
            if (in_array($ext, \App\Services\Uploads\SecureUpload::IMAGE_MIMES, true)) {
                $path = app(\App\Services\Uploads\SecureUpload::class)
                    ->storeImage($file, 'chat-attachments', 'public');
            } else {
                $path = $file->store('chat-attachments', 'public');
            }

            $attachmentData = [
                'attachment_path' => $path,
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getClientMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        // 💬 Save Message
        $message = ChatMessage::create(array_merge([
            'chat_thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'message' => $messageText,
            'is_flagged' => $isFlagged,
            'flag_level' => $flagLevel,
        ], $attachmentData));

        // 🚨 Notify Based on Severity Level
        if ($isFlagged && $flagLevel) {
            $rolesToNotify = config("chat.notify_roles.$flagLevel");
            $recipients = User::whereIn('role', $rolesToNotify)->get();
            foreach ($recipients as $user) {
                $user->notify(new FlaggedChatNotification($message));
            }
        }

        // 🔔 Notify other thread participants of the new message
        $senderName = $this->displayName(auth()->user());
        $otherParticipants = $thread->participants()
            ->where('users.id', '!=', auth()->id())
            ->get();

        foreach ($otherParticipants as $participant) {
            $participant->notify(new NewChatMessageNotification($message, $senderName));
        }

        if ($request->wantsJson() || $request->ajax()) {
            $me = auth()->user();
            $displayName = $this->displayName($me);
            $avatar = $me->profile_photo
                ? asset('storage/'.$me->profile_photo)
                : 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&background=e0e7ff&color=4f46e5';

            return response()->json([
                'message' => array_merge([
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $displayName,
                    'user_avatar' => $avatar,
                    'message' => $message->message,
                    'is_mine' => true,
                    'created_at' => optional($message->created_at)->format('M d, Y h:i A'),
                    'seen_by' => [],
                ], $this->attachmentPayload($message)),
            ]);
        }

        return back();
    }

    /**
     * Build the attachment payload for a message (includes expiry state).
     */
    protected function attachmentPayload(ChatMessage $m): array
    {
        if (! $m->attachment_name) {
            return ['attachment' => null];
        }

        $expiresAt = $m->created_at ? $m->created_at->copy()->addDay() : null;
        $expired = ! $m->attachment_path || ($expiresAt && now()->greaterThan($expiresAt));

        $ext = strtolower(pathinfo($m->attachment_name, PATHINFO_EXTENSION));
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'heic', 'heif'];
        $videoExts = ['mp4', 'mov', 'webm', 'ogg', 'avi', 'mkv', 'm4v', '3gp'];
        $officeExts = ['doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp'];

        $isImage = ($m->attachment_mime && str_starts_with($m->attachment_mime, 'image/'))
                   || in_array($ext, $imageExts, true);
        $isVideo = ($m->attachment_mime && str_starts_with($m->attachment_mime, 'video/'))
                   || in_array($ext, $videoExts, true);
        $isPdf = $ext === 'pdf' || $m->attachment_mime === 'application/pdf';
        $isOffice = in_array($ext, $officeExts, true);

        $previewUrl = null;
        if (! $expired) {
            if ($isPdf) {
                $previewUrl = asset('storage/'.$m->attachment_path);
            } elseif ($isOffice && $this->libreofficeAvailable()) {
                $previewUrl = route('communication.chat.attachment.preview', $m->id);
            }
        }

        return [
            'attachment' => [
                'name' => $m->attachment_name,
                'mime' => $m->attachment_mime,
                'size' => $m->attachment_size,
                'is_image' => $isImage,
                'is_video' => $isVideo,
                'is_pdf' => $isPdf,
                'is_office' => $isOffice,
                'expired' => $expired,
                'expires_at' => $expiresAt ? $expiresAt->format('M d, Y h:i A') : null,
                'url' => $expired ? null : asset('storage/'.$m->attachment_path),
                'preview_url' => $previewUrl,
                'download' => $expired ? null : route('communication.chat.attachment.download', $m->id),
            ],
        ];
    }

    /**
     * Is LibreOffice available for on-the-fly Office → PDF conversion?
     */
    protected function libreofficeAvailable(): bool
    {
        $path = config('chat.libreoffice_path');

        return $path && is_file($path);
    }

    /**
     * Return (and cache) a PDF preview of an Office document.
     * Conversion is done headlessly via LibreOffice; the cached PDF lives
     * next to the original upload and is removed by chat:purge-attachments.
     */
    public function attachmentPreview(ChatMessage $message)
    {
        $this->authorizeThread($message->thread);
        abort_if(! $message->attachment_path, 410, 'Attachment no longer available.');
        abort_unless($this->libreofficeAvailable(), 501, 'Preview converter not configured.');

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        abort_unless($disk->exists($message->attachment_path), 410);

        $source = $disk->path($message->attachment_path);
        $cacheRel = 'chat-attachments/previews/'.$message->id.'.pdf';
        $cacheAbs = $disk->path($cacheRel);

        if (! is_file($cacheAbs)) {
            @mkdir(dirname($cacheAbs), 0775, true);

            $soffice = config('chat.libreoffice_path');
            $outDir = dirname($cacheAbs);

            // Run LibreOffice in a private user profile so it doesn't conflict
            // with a running Office instance.
            $profile = storage_path('app/soffice-profile');
            @mkdir($profile, 0775, true);

            $cmd = sprintf(
                '"%s" --headless -env:UserInstallation=file:///%s --convert-to pdf --outdir "%s" "%s"',
                $soffice,
                str_replace('\\', '/', $profile),
                $outDir,
                $source
            );

            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $exit = proc_close($proc);
                \Log::debug('chat.preview.convert', compact('exit', 'stdout', 'stderr'));
            }

            // LibreOffice names the output after the source filename stem.
            $stem = pathinfo($source, PATHINFO_FILENAME);
            $produced = $outDir.DIRECTORY_SEPARATOR.$stem.'.pdf';
            if (is_file($produced) && $produced !== $cacheAbs) {
                @rename($produced, $cacheAbs);
            }

            abort_unless(is_file($cacheAbs), 500, 'Could not generate preview.');
        }

        return response()->file($cacheAbs, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.pathinfo($message->attachment_name, PATHINFO_FILENAME).'.pdf"',
        ]);
    }

    /**
     * Stream an attachment inline (for viewing in the preview modal / <img>).
     */
    public function attachment(ChatMessage $message)
    {
        $this->authorizeThread($message->thread);
        abort_if(! $message->attachment_path, 410, 'Attachment no longer available.');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        abort_unless($disk->exists($message->attachment_path), 410);

        $mime = $message->attachment_mime;
        if (! $mime || $mime === 'application/octet-stream') {
            $ext = strtolower(pathinfo($message->attachment_name, PATHINFO_EXTENSION));
            $map = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml', 'avif' => 'image/avif',
                'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
                'pdf' => 'application/pdf', 'txt' => 'text/plain',
            ];
            $mime = $map[$ext] ?? ($mime ?: 'application/octet-stream');
        }

        return response()->file($disk->path($message->attachment_path), [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Force-download an attachment.
     */
    public function downloadAttachment(ChatMessage $message)
    {
        $this->authorizeThread($message->thread);
        abort_if(! $message->attachment_path, 410, 'Attachment no longer available.');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        abort_unless($disk->exists($message->attachment_path), 410);

        $absolute = $disk->path($message->attachment_path);
        $filename = $message->attachment_name ?? basename($absolute);

        // Flush any accidental PHP output buffers so the binary stream is clean.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($absolute) {
            $handle = fopen($absolute, 'rb');
            if ($handle === false) {
                return;
            }
            while (! feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => $message->attachment_mime ?: 'application/octet-stream',
            'Content-Length' => (string) filesize($absolute),
            'Cache-Control' => 'private, max-age=0, no-transform',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function create(AssignableService $assignableService)
    {
        $schoolId = auth()->user()->school_id;

        $groups = $assignableService->getGroups($schoolId)->toArray();

        $users = User::where('id', '!=', auth()->id())->get();

        return view('communication.chat.create', compact('groups', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'assignments' => 'required|array|min:1',
        ]);

        // Extract only user assignments
        $participants = collect($request->assignments)
            ->filter(fn ($item) => str_starts_with($item, 'user:'))
            ->map(fn ($item) => (int) str_replace('user:', '', $item))
            ->values()
            ->all();

        if (count($participants) === 0) {
            return back()->withErrors(['assignments' => 'Please select at least one user.']);
        }

        $thread = ChatThread::create([
            'title' => $request->title,
            'type' => count($participants) > 1 ? 'group' : 'private',
            'school_id' => Auth::user()->school_id,
            'created_by' => Auth::id(),
            'status' => 'active',
        ]);

        $thread->participants()->attach($participants);
        $thread->participants()->attach(Auth::id());

        return redirect()->route('communication.chat.show', $thread);
    }

    protected function authorizeThread(ChatThread $thread)
    {
        $user = Auth::user();

        if (in_array($thread->type, ['private', 'group'])) {
            abort_unless(
                $thread->participants->contains('id', $user->id),
                403
            );
        }

        if ($thread->type === 'department') {
            abort_unless(
                $user->department_id === $thread->department_id,
                403
            );
        }

        if ($thread->type === 'class') {
            abort_unless(
                $user->classes->contains($thread->class_id),
                403
            );
        }
    }

    public function deleteMessage(ChatMessage $message)
    {
        abort_unless($message->user_id === auth()->id(), 403);

        $message->update([
            'deleted_by_user' => true,
        ]);

        $message->delete(); // soft delete

        return back();
    }

    /**
     * Delete an entire chat thread. Private: either participant. Group: creator only.
     */
    public function destroy(ChatThread $thread)
    {
        $this->authorizeThread($thread);
        $meId = auth()->id();

        if ($thread->type === 'group') {
            abort_unless($thread->created_by === $meId, 403, 'Only the creator can delete this group.');
        }

        $thread->messages()->delete();
        $thread->participants()->detach();
        $thread->delete();

        return response()->json(['success' => true]);
    }

    /**
     * List members of a thread.
     */
    public function members(ChatThread $thread)
    {
        $this->authorizeThread($thread);
        $meId = auth()->id();

        $thread->load('participants.profile');

        $members = $thread->participants->map(function ($p) use ($thread) {
            return [
                'id' => $p->id,
                'name' => $this->displayName($p),
                'email' => $p->email,
                'is_creator' => $p->id === $thread->created_by,
            ];
        })->values();

        return response()->json([
            'thread' => [
                'id' => $thread->id,
                'title' => $thread->title,
                'type' => $thread->type,
                'created_by' => $thread->created_by,
                'is_creator' => $thread->created_by === $meId,
            ],
            'members' => $members,
        ]);
    }

    /**
     * Add members to a group chat. Any existing member may add.
     */
    public function addMembers(Request $request, ChatThread $thread)
    {
        $this->authorizeThread($thread);
        abort_unless($thread->type === 'group', 422, 'Can only add members to group chats.');

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $existing = $thread->participants()->pluck('users.id')->toArray();
        $toAdd = array_diff($request->user_ids, $existing);

        if (count($toAdd) > 0) {
            $thread->participants()->attach($toAdd);
        }

        return $this->members($thread);
    }

    /**
     * Remove a member from a group chat. Creator only.
     */
    public function removeMember(ChatThread $thread, User $user)
    {
        $this->authorizeThread($thread);
        abort_unless($thread->type === 'group', 422, 'Can only remove members from group chats.');
        abort_unless($thread->created_by === auth()->id(), 403, 'Only the creator can remove members.');
        abort_if($user->id === $thread->created_by, 422, 'Creator cannot be removed.');

        $thread->participants()->detach($user->id);

        return $this->members($thread);
    }

    public function requestDeletion(ChatThread $thread)
    {
        abort_unless($thread->created_by === auth()->id(), 403);

        $thread->update([
            'status' => 'pending_deletion',
        ]);

        return back();
    }
}
