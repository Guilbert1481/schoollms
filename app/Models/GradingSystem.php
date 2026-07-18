<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSystem extends Model
{
    use Auditable, HasFactory; // admin-action log (Phase 4)

    protected $fillable = [
        'name',
        'type',
        'passing_mark',
        'is_default',
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
