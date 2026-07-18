<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One append-only audit row (Roadmap Phase 3 / H2): who (actor) changed what
 * (auditable model, before/after) in which school, when. Written exclusively
 * by the Auditable trait's model events — never create rows by hand, never
 * update or delete existing ones.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'school_id',
        'actor_id',
        'event',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
