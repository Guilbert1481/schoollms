<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriveFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'owner_id',
        'parent_id',
        'type',
        'name',
        'mime',
        'extension',
        'size',
        'path',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DriveFileShare::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'drive_file_shares')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function edits(): HasMany
    {
        return $this->hasMany(DriveFileEdit::class);
    }

    public function lastEdit()
    {
        return $this->hasOne(DriveFileEdit::class)->latestOfMany();
    }

    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /* ---------- Presentation accessors (used by x-table.table) ---------- */

    public function getTypeLabelAttribute(): string
    {
        if ($this->isFolder()) return 'Folder';
        return strtoupper($this->extension ?: 'File');
    }

    public function getSizeHumanAttribute(): string
    {
        if ($this->isFolder()) return '—';
        $bytes = (int) $this->size;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    public function getOwnerNameAttribute(): string
    {
        $owner = $this->owner;
        if (!$owner) return 'Unknown';
        $profile = $owner->profile ?? null;
        $name = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''));
        return $name !== '' ? $name : ($owner->email ?? 'User #' . $owner->id);
    }

    public function getUpdatedAtLabelAttribute(): string
    {
        return optional($this->updated_at)->format('M d, Y h:i A') ?? '—';
    }

    public function getLastEditorNameAttribute(): string
    {
        $edit = $this->relationLoaded('lastEdit') ? $this->lastEdit : $this->lastEdit()->with('user.profile')->first();
        if (!$edit || !$edit->user) return '—';
        $profile = $edit->user->profile ?? null;
        $name = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''));
        return $name !== '' ? $name : ($edit->user->email ?? 'User #' . $edit->user->id);
    }
}
