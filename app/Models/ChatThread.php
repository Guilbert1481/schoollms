<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class ChatThread extends Model
{
    use SoftDeletes, BelongsToSchool;

     /**
     * Mass assignable attributes
     */
     protected $table = 'chat_threads';

     protected $dates = ['deleted_at'];

     protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name', 
        'type',
        'school_id',
        'created_by',
        'status',
        'title',
    ];

    public function participants()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }


    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
