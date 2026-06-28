<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'school_id',
        'name',
        'authority_level',
        'is_head_role',
        'badge_color',
        'badge_text_color',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function byName(string $name)
    {
        return static::where('name', $name)->first();
    }

    public static function authority(string $name)
    {
        return static::where('name', $name)->value('authority_level');
    }
}
