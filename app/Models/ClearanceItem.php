<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClearanceItem extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CLEARED = 'cleared';

    public const STATUS_HOLD = 'hold';

    protected $fillable = [
        'school_id',
        'clearance_id',
        'label',
        'clearance_signatory_id',
        'subject_id',
        'teacher_user_id',
        'status',
        'acted_by',
        'acted_at',
        'remarks',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function clearance()
    {
        return $this->belongsTo(Clearance::class);
    }

    public function signatory()
    {
        return $this->belongsTo(ClearanceSignatory::class, 'clearance_signatory_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }
}
