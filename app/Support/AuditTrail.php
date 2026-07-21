<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Roadmap S4 — a second, off-database copy of the security trail.
 *
 * Every audit_logs row (written by {@see \App\Models\Traits\Auditable}) and
 * every login_logs row (written by {@see \App\Listeners\LogAuthenticationActivity})
 * is ALSO emitted here, to a dedicated log channel — by default the append-only
 * `audit` file channel (config/logging.php), which the operator ships off-box.
 * Point `AUDIT_LOG_CHANNEL` at `syslog`/`papertrail` for true off-host durability.
 *
 * Why: the database rows are the queryable source of truth (the superadmin
 * Logins page, the finance audit views), but an attacker who reaches the DB
 * can `TRUNCATE audit_logs` and erase their own trail. This mirror lives
 * outside the database, so the trail survives a DB-level compromise.
 *
 * This is a passive backup, never a gate: a failure to write the mirror must
 * never break the mutation that produced it (the DB row is already the record
 * of truth), so failures are swallowed to the default channel as a breadcrumb.
 */
class AuditTrail
{
    /**
     * @param  string  $kind  'data' for model audits, 'auth' for login events.
     * @param  array<string, mixed>  $entry
     */
    public static function record(string $kind, array $entry): void
    {
        try {
            Log::channel(config('logging.audit_channel', 'audit'))
                ->info('audit.'.$kind, $entry);
        } catch (\Throwable $e) {
            Log::error('audit.trail.mirror_failed', [
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
