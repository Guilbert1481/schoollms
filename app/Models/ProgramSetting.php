<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'enforce_capacity',
        'waitlist_enabled',
        'default_delivery_mode',
        'allow_cross_program_enrollment'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
