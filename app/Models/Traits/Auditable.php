<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Passive audit observer (Roadmap Phase 3 / H2): models using this trait get
 * an append-only audit_logs row on create / update / delete, recording the
 * actor, school, and before/after values. Purely additive — it never alters
 * the mutation itself, and it runs inside the caller's transaction so a
 * money change and its audit row commit (or roll back) together.
 *
 * Options (define on the model when needed):
 * - `protected array $auditOnly = [...]` — audit ONLY these attributes and
 *   skip the event entirely when none of them changed (e.g. User: role).
 * - `protected array $auditExclude = [...]` — additionally hide attributes.
 *   Secrets (password, remember_token, api_key) are always excluded.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', null, $model->auditableValues($model->getAttributes()));
        });

        static::updated(function ($model) {
            // In the `updated` event getChanges() holds the just-saved dirty
            // set and getOriginal() still holds pre-save values.
            $changes = $model->auditableValues($model->getChanges());
            if ($changes === []) {
                return;
            }

            $before = array_intersect_key($model->getOriginal(), $changes);

            $model->writeAudit('updated', $before, $changes);
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->auditableValues($model->getAttributes()), null);
        });
    }

    protected function writeAudit(string $event, ?array $before, ?array $after): void
    {
        $row = AuditLog::create([
            'school_id' => $this->getAttribute('school_id') ?? Auth::user()?->school_id,
            'actor_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => (int) $this->getKey(),
            'before' => $before,
            'after' => $after,
        ]);

        // S4 — mirror to the off-database trail, but only once the caller's
        // transaction actually commits. The audit row lives inside that same
        // transaction (Phase 3), so if a money change rolls back, this callback
        // is dropped and the mirror stays in lockstep with the table. Outside a
        // transaction, afterCommit() runs the callback immediately.
        DB::afterCommit(fn () => AuditTrail::record('data', [
            'id' => $row->getKey(),
            'school_id' => $row->school_id,
            'actor_id' => $row->actor_id,
            'event' => $row->event,
            'auditable_type' => $row->auditable_type,
            'auditable_id' => $row->auditable_id,
            'before' => $before,
            'after' => $after,
            'at' => optional($row->created_at)->toIso8601String(),
        ]));
    }

    /** Filter an attribute set down to what this model audits. */
    protected function auditableValues(array $attributes): array
    {
        $always = ['password', 'remember_token', 'api_key', 'created_at', 'updated_at', 'deleted_at'];
        $exclude = array_merge($always, property_exists($this, 'auditExclude') ? $this->auditExclude : []);

        $attributes = array_diff_key($attributes, array_flip($exclude));

        if (property_exists($this, 'auditOnly') && $this->auditOnly !== []) {
            $attributes = array_intersect_key($attributes, array_flip($this->auditOnly));
        }

        return $attributes;
    }
}
