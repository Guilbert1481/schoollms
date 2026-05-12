<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'template_id',
        'event_name',
        'event_type',
        'certificate_title_default',
        'date_issued_default',
        'metadata',
    ];

    protected $casts = [
        'date_issued_default' => 'date',
        'metadata' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function recipients()
    {
        return $this->hasMany(CertificateEventRecipient::class, 'event_id');
    }

    public function eventTypes()
    {
        return $this->hasMany(EventType::class, 'event_id');
    }

    public function eventRoles()
    {
        return $this->hasMany(EventRole::class, 'event_id');
    }
}
