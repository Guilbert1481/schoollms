<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentSignatory extends Model
{
    use HasFactory;

    protected $table = 'document_signatories';

    protected $fillable = [
        'document_id',
        'signatory_id',
        'sign_order',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function signatory()
    {
        return $this->belongsTo(Signatory::class);
    }
}