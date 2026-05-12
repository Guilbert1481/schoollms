<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\DriveFile;
use App\Models\DriveFileShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sophentis Drive — Google-Drive-style file sharing for school users.
 *
 * Permission model:
 *  - Owner: full control (rename, share, delete, replace content).
 *  - Shared "view": can preview + download only.
 *  - Shared "edit": preview + download + replace content + rename.
 *
 * Files are stored on the 'public' disk under drive/{owner_id}/... with
 * randomized filenames. Folders are DB-only (no disk directory).
 */
class DriveController extends Controller
{
    // ================================================================
    // PAGE
    // ================================================================
    public function index(Request $request)
    {
        $user     = Auth::user();
        $scope    = $request->query('scope', 'my') === 'shared' ? 'shared' : 'my';
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

        $parent      = null;
        $breadcrumbs = [];
        $items       = collect();

        if ($scope === 'shared') {
            if ($parentId) {
                // Drilled into a shared folder: list its children (full subtree),
                // provided the viewer has at least 'view' permission somewhere up the chain.
                $parent = DriveFile::findOrFail($parentId);
                abort_unless($this->canAccess($parent, 'view', $user), 403);
                $breadcrumbs = $this->breadcrumbs($parent);

                $items = DriveFile::with(['owner.profile', 'lastEdit.user.profile'])
                    ->where('parent_id', $parentId)
                    ->orderBy('type', 'desc')
                    ->orderBy('name')
                    ->get();
            } else {
                // Top-level: items directly shared with me.
                $shareRows = DriveFileShare::with('file.owner.profile', 'file.lastEdit.user.profile')
                    ->where('user_id', $user->id)
                    ->get();

                $items = $shareRows
                    ->filter(fn ($s) => $s->file)
                    ->map(function ($s) {
                        $file = $s->file;
                        $file->setAttribute('shared_permission', $s->permission);
                        return $file;
                    })
                    ->sortBy([['type', 'desc'], ['name', 'asc']])
                    ->values();
            }
        } else {
            if ($parentId) {
                $parent = DriveFile::findOrFail($parentId);
                abort_unless($this->canAccess($parent, 'view', $user), 403);
                $breadcrumbs = $this->breadcrumbs($parent);
            }

            $query = DriveFile::with(['owner.profile', 'lastEdit.user.profile'])->where('parent_id', $parentId);

            // Only filter by owner when browsing my own tree.
            if (!$parent || $parent->owner_id === $user->id) {
                $query->where('owner_id', $user->id);
            }

            $items = $query
                ->orderBy('type', 'desc')
                ->orderBy('name')
                ->get();
        }

        // Build a lightweight JSON map for front-end actions (view, share, delete).
        $itemsMap = $items->mapWithKeys(fn ($f) => [
            $f->id => $this->fileJson($f, $user),
        ])->toArray();

        $columns = config('tables.drive_files.columns');
        $actions = config('tables.table-actions.drive_files');

        // Can the viewer upload / create folders here?
        //  - My Drive: always (at any depth you own, or in a shared folder you can edit).
        //  - Shared: only when drilled into a folder the viewer has 'edit' access on.
        $canUploadHere = false;
        if ($scope === 'my') {
            $canUploadHere = !$parent || $parent->owner_id === $user->id || $this->canAccess($parent, 'edit', $user);
        } elseif ($parent) {
            $canUploadHere = $this->canAccess($parent, 'edit', $user);
        }

        return view('tools.drive.index', [
            'scope'         => $scope,
            'parentId'      => $parentId,
            'parent'        => $parent,
            'breadcrumbs'   => $breadcrumbs,
            'data'          => $items,
            'columns'       => $columns,
            'actions'       => $actions,
            'itemsMap'      => $itemsMap,
            'canUploadHere' => $canUploadHere,
        ]);
    }

    // ================================================================
    // LIST ITEMS  (JSON)
    //
    // Modes:
    //   scope=my        items I own inside ?parent_id
    //   scope=shared    items shared with me (top-level roots only)
    // ================================================================
    public function items(Request $request)
    {
        $user  = Auth::user();
        $scope = $request->string('scope', 'my')->toString();

        if ($scope === 'shared') {
            return $this->listSharedRoots($user);
        }

        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

        $parent = null;
        if ($parentId) {
            $parent = DriveFile::findOrFail($parentId);
            abort_unless($this->canAccess($parent, 'view', $user), 403);
        }

        $query = DriveFile::query()
            ->where('parent_id', $parentId);

        // If I'm inside a folder I don't own, show ALL of its children (shared-tree browsing).
        // Otherwise show only items I own.
        if (!$parent || $parent->owner_id === $user->id) {
            $query->where('owner_id', $user->id);
        }

        $items = $query
            ->orderBy('type', 'desc') // folders first
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => $this->fileJson($f, $user));

        return response()->json([
            'breadcrumbs' => $this->breadcrumbs($parent),
            'parent'      => $parent ? $this->fileJson($parent, $user) : null,
            'items'       => $items,
        ]);
    }

    protected function listSharedRoots(User $user)
    {
        $shareRows = DriveFileShare::with('file.owner.profile')
            ->where('user_id', $user->id)
            ->get();

        $items = $shareRows
            ->filter(fn ($s) => $s->file)
            ->map(function ($s) use ($user) {
                $json = $this->fileJson($s->file, $user);
                $json['shared_permission'] = $s->permission;
                $json['shared_by']         = $this->displayName($s->file->owner);
                return $json;
            })
            ->values();

        return response()->json([
            'breadcrumbs' => [],
            'parent'      => null,
            'items'       => $items,
        ]);
    }

    protected function breadcrumbs(?DriveFile $parent): array
    {
        if (!$parent) return [];
        $chain = [];
        $cursor = $parent;
        while ($cursor) {
            array_unshift($chain, ['id' => $cursor->id, 'name' => $cursor->name]);
            $cursor = $cursor->parent;
        }
        return $chain;
    }

    // ================================================================
    // CREATE FOLDER
    // ================================================================
    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:drive_files,id',
        ]);

        $user = Auth::user();

        if (!empty($data['parent_id'])) {
            $parent = DriveFile::findOrFail($data['parent_id']);
            abort_unless($this->canAccess($parent, 'edit', $user) || $parent->owner_id === $user->id, 403);
        }

        $folder = DriveFile::create([
            'school_id' => $user->school_id,
            'owner_id'  => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'type'      => 'folder',
            'name'      => $data['name'],
        ]);

        return response()->json(['item' => $this->fileJson($folder, $user)], 201);
    }

    // ================================================================
    // UPLOAD FILE(S)
    // ================================================================
    public function storeFiles(Request $request)
    {
        $request->validate([
            'files'     => 'required',
            'files.*'   => 'file|max:102400', // 100 MB per file
            'parent_id' => 'nullable|integer|exists:drive_files,id',
        ]);

        $user     = Auth::user();
        $parentId = $request->input('parent_id') ?: null;

        if ($parentId) {
            $parent = DriveFile::findOrFail($parentId);
            abort_unless(
                $parent->type === 'folder'
                && ($parent->owner_id === $user->id || $this->canAccess($parent, 'edit', $user)),
                403
            );
        }

        $created = [];
        foreach ((array) $request->file('files') as $file) {
            if (!$file || !$file->isValid()) continue;

            $dir  = 'drive/' . $user->id;
            $path = $file->store($dir, 'public');

            $record = DriveFile::create([
                'school_id' => $user->school_id,
                'owner_id'  => $user->id,
                'parent_id' => $parentId,
                'type'      => 'file',
                'name'      => $file->getClientOriginalName(),
                'mime'      => $file->getClientMimeType(),
                'extension' => strtolower($file->getClientOriginalExtension()),
                'size'      => $file->getSize(),
                'path'      => $path,
            ]);

            $created[] = $this->fileJson($record, $user);
        }

        return response()->json(['items' => $created], 201);
    }

    // ================================================================
    // RENAME
    // ================================================================
    public function rename(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($this->canAccess($file, 'edit', $user), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $file->update(['name' => $data['name']]);

        \App\Models\DriveFileEdit::create([
            'drive_file_id' => $file->id,
            'user_id'       => $user->id,
            'action'        => 'rename',
            'summary'       => 'Renamed to "' . $data['name'] . '"',
        ]);

        return response()->json(['item' => $this->fileJson($file, $user)]);
    }

    // ================================================================
    // REPLACE (upload new content for an existing file)
    // ================================================================
    public function replace(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFile() && $this->canAccess($file, 'edit', $user), 403);

        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        $disk   = Storage::disk('public');
        $upload = $request->file('file');

        // Delete the old content + any cached preview PDF.
        if ($file->path && $disk->exists($file->path)) {
            $disk->delete($file->path);
        }
        $this->deletePreviewCache($file);

        $newPath = $upload->store('drive/' . $file->owner_id, 'public');

        $file->update([
            'mime'      => $upload->getClientMimeType(),
            'extension' => strtolower($upload->getClientOriginalExtension()),
            'size'      => $upload->getSize(),
            'path'      => $newPath,
        ]);

        \App\Models\DriveFileEdit::create([
            'drive_file_id' => $file->id,
            'user_id'       => $user->id,
            'action'        => 'replace',
            'summary'       => 'Replaced file content',
        ]);

        return response()->json(['item' => $this->fileJson($file, $user)]);
    }

    // ================================================================
    // TEXT CONTENT (read / write) — like Google Docs autosave
    // ================================================================
    public function getContent(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFile() && $this->canOpenFile($file, $user), 403);

        if (!$this->isTextEditable($file)) {
            return response()->json([
                'editable' => false,
                'message'  => 'This file type cannot be edited in-browser. Use "Replace file" to upload a new version.',
                'content'  => null,
            ]);
        }

        $disk = Storage::disk('public');
        $content = $disk->exists($file->path) ? $disk->get($file->path) : '';

        return response()->json([
            'editable' => true,
            'content'  => $content,
            'can_edit' => $this->canOpenFile($file, $user) && $this->canAccess($file, 'edit', $user),
        ]);
    }

    public function putContent(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFile() && $this->canOpenFile($file, $user) && $this->canAccess($file, 'edit', $user), 403);
        abort_unless($this->isTextEditable($file), 415, 'File type cannot be edited in-browser.');

        $data = $request->validate([
            'content' => 'present|string',
        ]);

        $disk = Storage::disk('public');
        $disk->put($file->path, $data['content']);

        // Refresh cached previews.
        $this->deletePreviewCache($file);

        $file->update([
            'size' => strlen($data['content']),
        ]);

        \App\Models\DriveFileEdit::create([
            'drive_file_id' => $file->id,
            'user_id'       => $user->id,
            'action'        => 'edit',
            'summary'       => 'Edited content',
        ]);

        return response()->json([
            'ok'         => true,
            'size_human' => $this->humanSize((int) $file->size),
            'saved_at'   => now()->format('M d, Y h:i A'),
        ]);
    }

    public function history(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($this->canOpenFile($file, $user), 403);

        $edits = $file->edits()
            ->with('user.profile')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($e) => [
                'id'       => $e->id,
                'user'     => $this->displayName($e->user),
                'action'   => $e->action,
                'summary'  => $e->summary,
                'at'       => optional($e->created_at)->format('M d, Y h:i A'),
            ]);

        return response()->json(['edits' => $edits]);
    }

    protected function isTextEditable(DriveFile $file): bool
    {
        $ext = strtolower($file->extension ?? '');
        $textExts = ['txt','md','markdown','csv','log','json','xml','html','htm','css','js','ts','py','php','rb','go','java','c','cpp','h','yml','yaml','ini','sql','env','sh','bat'];
        return in_array($ext, $textExts, true);
    }

    // ================================================================
    // DELETE
    // ================================================================
    public function destroy(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->owner_id === $user->id, 403);

        $this->deleteRecursive($file);

        return response()->json(['ok' => true]);
    }

    protected function deleteRecursive(DriveFile $file): void
    {
        $disk = Storage::disk('public');

        if ($file->isFolder()) {
            foreach ($file->children()->get() as $child) {
                $this->deleteRecursive($child);
            }
        } else {
            if ($file->path && $disk->exists($file->path)) {
                $disk->delete($file->path);
            }
            $this->deletePreviewCache($file);
        }

        $file->delete();
    }

    // ================================================================
    // PREVIEW (inline stream; Office → PDF via LibreOffice)
    // ================================================================
    public function preview(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFile() && $this->canOpenFile($file, $user), 403);

        $disk = Storage::disk('public');
        abort_if(!$file->path || !$disk->exists($file->path), 410);

        $ext         = strtolower($file->extension ?: pathinfo($file->name, PATHINFO_EXTENSION));
        $officeExts  = ['doc','docx','odt','rtf','xls','xlsx','ods','csv','ppt','pptx','odp'];

        // Office documents → convert to PDF (cached) so the browser can render them.
        if (in_array($ext, $officeExts, true) && $this->libreofficeAvailable()) {
            $pdfAbs = $this->convertOfficeToPdf($file);
            abort_unless($pdfAbs && is_file($pdfAbs), 500, 'Preview conversion failed.');

            return response()->file($pdfAbs, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . pathinfo($file->name, PATHINFO_FILENAME) . '.pdf"',
            ]);
        }

        // Everything else: stream with a sensible content type.
        $mime = $this->resolveMime($file, $ext);

        return response()->file($disk->path($file->path), [
            'Content-Type' => $mime,
        ]);
    }

    // ================================================================
    // DOWNLOAD
    // ================================================================
    public function download(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFile() && $this->canOpenFile($file, $user), 403);

        $disk = Storage::disk('public');
        abort_if(!$file->path || !$disk->exists($file->path), 410);

        $absolute = $disk->path($file->path);

        while (ob_get_level() > 0) { ob_end_clean(); }

        return response()->streamDownload(function () use ($absolute) {
            $handle = fopen($absolute, 'rb');
            if ($handle === false) return;
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
        }, $file->name, [
            'Content-Type'           => $file->mime ?: 'application/octet-stream',
            'Content-Length'         => (string) filesize($absolute),
            'Cache-Control'          => 'private, max-age=0, no-transform',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ================================================================
    // ZIP DOWNLOAD (folder tree)
    //
    // The folder itself (or any ancestor) must belong to the viewer — i.e.
    // the same rule as opening a file inside the folder. This is the
    // "sender downloads everything collected in their shared folder" flow.
    // ================================================================
    public function zip(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->isFolder(), 404);
        abort_unless($this->canOpenFile($file, $user), 403);

        $disk = Storage::disk('public');

        // Build zip in a temporary file.
        $tmp = tempnam(sys_get_temp_dir(), 'drive_zip_');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            @unlink($tmp);
            abort(500, 'Could not create archive.');
        }

        $this->addFolderToZip($zip, $file, $disk, '');

        // If the folder was empty, add a placeholder so zip isn't invalid.
        if ($zip->numFiles === 0) {
            $zip->addFromString($file->name . '/.empty', '');
        }

        $zip->close();

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->name) . '.zip';

        while (ob_get_level() > 0) { ob_end_clean(); }

        return response()->streamDownload(function () use ($tmp) {
            $handle = fopen($tmp, 'rb');
            if ($handle === false) return;
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
            @unlink($tmp);
        }, $safeName, [
            'Content-Type'           => 'application/zip',
            'Content-Length'         => (string) filesize($tmp),
            'Cache-Control'          => 'private, max-age=0, no-transform',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Recursively adds a DriveFile folder (and its children) into the ZipArchive.
     * Only includes files the viewer can open (owner or ancestor-folder owner),
     * so the recipient cannot weaponize the zip endpoint to exfiltrate
     * unrelated files.
     */
    protected function addFolderToZip(\ZipArchive $zip, DriveFile $folder, $disk, string $prefix): void
    {
        $viewer = Auth::user();
        $base = ($prefix === '' ? '' : rtrim($prefix, '/') . '/') . $folder->name;
        $zip->addEmptyDir($base);

        foreach ($folder->children()->orderBy('type', 'desc')->orderBy('name')->get() as $child) {
            if ($child->isFolder()) {
                $this->addFolderToZip($zip, $child, $disk, $base);
            } else {
                if (!$this->canOpenFile($child, $viewer)) continue;
                if (!$child->path || !$disk->exists($child->path)) continue;
                $zip->addFile($disk->path($child->path), $base . '/' . $child->name);
            }
        }
    }

    // ================================================================
    // SHARING
    // ================================================================
    public function shares(DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->owner_id === $user->id, 403);

        $list = $file->shares()->with('user.profile')->get()->map(function ($s) {
            return [
                'user_id'    => $s->user_id,
                'name'       => $this->displayName($s->user),
                'permission' => $s->permission,
            ];
        });

        return response()->json(['shares' => $list]);
    }

    /**
     * Sync the full list of shares for a file.
     * Body: { shares: [ { user_id, permission }, ... ] }
     */
    public function share(Request $request, DriveFile $file)
    {
        $user = Auth::user();
        abort_unless($file->owner_id === $user->id, 403);

        $data = $request->validate([
            'shares'              => 'array',
            'shares.*.user_id'    => 'required|integer|exists:users,id',
            'shares.*.permission' => 'required|in:view,edit',
        ]);

        DB::transaction(function () use ($file, $data, $user) {
            $file->shares()->delete();
            foreach (($data['shares'] ?? []) as $row) {
                if ((int) $row['user_id'] === $user->id) continue; // never share to self
                DriveFileShare::create([
                    'drive_file_id' => $file->id,
                    'user_id'       => $row['user_id'],
                    'permission'    => $row['permission'],
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function searchUsers(Request $request)
    {
        $me = Auth::user();
        $q  = trim((string) $request->input('q', ''));

        if ($q === '') return response()->json([]);

        $users = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->where('users.id', '!=', $me->id)
            ->where(function ($w) use ($q) {
                $w->where('users.email', 'like', "%{$q}%")
                  ->orWhere('profiles.first_name', 'like', "%{$q}%")
                  ->orWhere('profiles.last_name', 'like', "%{$q}%");
            })
            ->select('users.id', 'users.email', 'profiles.first_name', 'profiles.last_name')
            ->limit(10)
            ->get()
            ->map(function ($u) {
                $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                return [
                    'id'    => $u->id,
                    'name'  => $name !== '' ? $name : $u->email,
                    'email' => $u->email,
                ];
            });

        return response()->json($users);
    }

    // ================================================================
    // HELPERS
    // ================================================================
    protected function canAccess(DriveFile $file, string $level, User $user): bool
    {
        if ($file->owner_id === $user->id) return true;

        // If this item is nested under an accessible shared folder, inherit that access.
        $cursor = $file;
        while ($cursor) {
            $share = DriveFileShare::where('drive_file_id', $cursor->id)
                ->where('user_id', $user->id)
                ->first();
            if ($share) {
                if ($level === 'view') return true;
                if ($level === 'edit') return $share->permission === 'edit';
            }
            $cursor = $cursor->parent;
        }

        return false;
    }

    /**
     * Can the viewer open (preview/download/edit) the *content* of this file?
     *
     * Rule: yes if they own the file, or they own any ancestor folder.
     * This means a shared folder's sender can open files contributed by
     * recipients, but recipients cannot open each other's files nor the
     * original files that live in the sender's tree.
     */
    protected function canOpenFile(DriveFile $file, User $user): bool
    {
        if ($file->owner_id === $user->id) return true;
        $cursor = $file->parent;
        while ($cursor) {
            if ($cursor->owner_id === $user->id) return true;
            $cursor = $cursor->parent;
        }
        return false;
    }

    protected function fileJson(DriveFile $f, User $viewer): array
    {
        $isImage = $f->isFile() && $f->mime && str_starts_with($f->mime, 'image/');
        $isVideo = $f->isFile() && $f->mime && str_starts_with($f->mime, 'video/');
        $isPdf   = $f->isFile() && ($f->mime === 'application/pdf' || strtolower($f->extension ?? '') === 'pdf');
        $officeExts = ['doc','docx','odt','rtf','xls','xlsx','ods','csv','ppt','pptx','odp'];
        $isOffice   = $f->isFile() && in_array(strtolower($f->extension ?? ''), $officeExts, true);
        $isText     = $f->isFile() && $this->isTextEditable($f);

        // A file (leaf) can only be opened by its owner OR by any user who owns
        // an ancestor folder (so the folder-sender can open contributions made
        // by recipients inside their shared folder). Folders are always openable.
        $canOpen = $f->isFolder() || $this->canOpenFile($f, $viewer);

        return [
            'id'          => $f->id,
            'type'        => $f->type,
            'name'        => $f->name,
            'mime'        => $f->mime,
            'extension'   => $f->extension,
            'size'        => $f->size,
            'owner_id'    => $f->owner_id,
            'owner_name'  => $this->displayName($f->owner),
            'size_human'  => $f->isFile() ? $this->humanSize((int) $f->size) : '—',
            'is_mine'     => $f->owner_id === $viewer->id,
            'can_open'    => $canOpen,
            'is_image'    => $isImage,
            'is_video'    => $isVideo,
            'is_pdf'      => $isPdf,
            'is_office'   => $isOffice,
            'is_text'     => $isText,
            'updated_at'  => optional($f->updated_at)->format('M d, Y h:i A'),
            'preview_url' => $f->isFile() ? route('tools.drive.preview',  $f->id) : null,
            'download'    => $f->isFile() ? route('tools.drive.download', $f->id) : null,
            'zip_url'     => ($f->isFolder() && $this->canOpenFile($f, $viewer)) ? route('tools.drive.zip', $f->id) : null,
            'content_url' => $f->isFile() ? route('tools.drive.content.get', $f->id) : null,
            'history_url' => route('tools.drive.history', $f->id),
        ];
    }

    protected function displayName(?User $u): string
    {
        if (!$u) return 'Unknown';
        $profile = $u->profile ?? null;
        $name = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''));
        return $name !== '' ? $name : ($u->email ?? 'User #' . $u->id);
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    protected function libreofficeAvailable(): bool
    {
        $path = config('chat.libreoffice_path');
        return $path && is_file($path);
    }

    protected function convertOfficeToPdf(DriveFile $file): ?string
    {
        $disk   = Storage::disk('public');
        $source = $disk->path($file->path);

        $cacheRel = 'drive/previews/' . $file->id . '.pdf';
        $cacheAbs = $disk->path($cacheRel);

        // Invalidate cache if the source is newer than the cached preview.
        if (is_file($cacheAbs) && filemtime($cacheAbs) >= filemtime($source)) {
            return $cacheAbs;
        }

        @mkdir(dirname($cacheAbs), 0775, true);

        $soffice = config('chat.libreoffice_path');
        $outDir  = dirname($cacheAbs);
        $profile = storage_path('app/soffice-profile');
        @mkdir($profile, 0775, true);

        $cmd = sprintf(
            '"%s" --headless -env:UserInstallation=file:///%s --convert-to pdf --outdir "%s" "%s"',
            $soffice,
            str_replace('\\', '/', $profile),
            $outDir,
            $source
        );

        $descriptors = [1 => ['pipe','w'], 2 => ['pipe','w']];
        $proc = proc_open($cmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $exit   = proc_close($proc);
            Log::debug('drive.preview.convert', compact('exit', 'stdout', 'stderr'));
        }

        $stem     = pathinfo($source, PATHINFO_FILENAME);
        $produced = $outDir . DIRECTORY_SEPARATOR . $stem . '.pdf';
        if (is_file($produced) && $produced !== $cacheAbs) {
            @rename($produced, $cacheAbs);
        }

        return is_file($cacheAbs) ? $cacheAbs : null;
    }

    protected function deletePreviewCache(DriveFile $file): void
    {
        $disk     = Storage::disk('public');
        $cacheRel = 'drive/previews/' . $file->id . '.pdf';
        if ($disk->exists($cacheRel)) {
            $disk->delete($cacheRel);
        }
    }

    protected function resolveMime(DriveFile $file, string $ext): string
    {
        if ($file->mime && $file->mime !== 'application/octet-stream') {
            return $file->mime;
        }
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif',  'webp' => 'image/webp', 'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',  'webm' => 'video/webm', 'mov' => 'video/quicktime',
            'pdf' => 'application/pdf', 'txt' => 'text/plain',
        ];
        return $map[$ext] ?? ($file->mime ?: 'application/octet-stream');
    }
}
