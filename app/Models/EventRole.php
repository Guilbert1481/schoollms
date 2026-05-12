<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
    ];

    public function event()
    {
        return $this->belongsTo(CertificateEvent::class, 'event_id');
    }
}
