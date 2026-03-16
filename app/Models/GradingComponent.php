<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_system_id',
        'name',
        'weight_percentage'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function gradingSystem()
    {
        return $this->belongsTo(GradingSystem::class);
    }
}
