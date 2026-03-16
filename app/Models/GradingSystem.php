<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'passing_mark',
        'is_default'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function components()
    {
        return $this->hasMany(GradingComponent::class);
    }

    public function classTemplates()
    {
        return $this->hasMany(ClassGradingTemplate::class);
    }
}
