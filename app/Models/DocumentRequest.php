<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_RELEASED = 'released';

    public const STATUS_DENIED = 'denied';

    /** Allowed forward transitions per current status (denial only pre-release). */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_DENIED],
        self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_DENIED],
        self::STATUS_READY => [self::STATUS_RELEASED, self::STATUS_DENIED],
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'document_id',
        'purpose',
        'copies',
        'status',
        'handled_by',
        'remarks',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
