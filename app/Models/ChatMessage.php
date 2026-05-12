<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class ChatMessage extends Model
{
    use SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'chat_thread_id',
        'user_id',
        'message',
        'deleted_by_user',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'is_flagged',
        'flag_level',
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
