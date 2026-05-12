<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveFileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'drive_file_id',
        'user_id',
        'permission',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(DriveFile::class, 'drive_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
