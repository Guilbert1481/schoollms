<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'recipient_id',
        'attendance_date',
        'status',
        'time_in_at',
        'time_out_at',
        'capture_source',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(CertificateEvent::class, 'event_id');
    }

    public function recipient()
    {
        return $this->belongsTo(CertificateEventRecipient::class, 'recipient_id');
    }
}
