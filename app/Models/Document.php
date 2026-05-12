<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'number_of_signatories',
        'processing_days',
        'fee',
        'request_count',
        'is_active'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Signatories assigned to this document
    public function signatories()
    {
        return $this->hasMany(DocumentSignatory::class);
    }

    // Document requests
    public function requests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function documentSignatories()
{
    return $this->hasMany(DocumentSignatory::class);
}



}