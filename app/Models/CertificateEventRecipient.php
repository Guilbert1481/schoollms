<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateEventRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'recipient_template_id',
        'recipient_name',
        'certificate_title',
        'award_title',
        'activity_name',
        'recognition_reason',
        'organization_name',
        'signatory_name',
        'issued_date',
        'custom_fields',
        'generated_file_path',
        'status',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'custom_fields' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(CertificateEvent::class, 'event_id');
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'recipient_template_id');
    }

    public function attendances()
    {
        return $this->hasMany(EventAttendance::class, 'recipient_id');
    }
}
