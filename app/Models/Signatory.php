<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Signatory extends Model
{
    use HasFactory;

    protected $table = 'signatories';

    protected $fillable = [
        'name',
        'position',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Documents where this signatory is assigned
    public function documentSignatories()
    {
        return $this->hasMany(DocumentSignatory::class);
    }

    // Optional: if linked to system user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}