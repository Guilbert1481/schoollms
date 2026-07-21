<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Roadmap S4 — the security trail is mirrored off the app database so a
 * DB-level attacker who truncates audit_logs / login_logs cannot erase it.
 * These tests point the `audit` channel at a known temp file and assert every
 * audited mutation and login event also lands there, in lockstep with the DB
 * rows (and — critically — that a rolled-back mutation leaves NO phantom line).
 */
class AuditTrailOffDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $actor;

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actor = User::factory()->create(['school_id' => $this->school->id, 'role' => 'finance_manager']);

        // Redirect the off-DB channel to a single, predictable temp file so the
        // test can read exactly what was mirrored (the real channel is a dated
        // `daily` file). forgetChannel() drops any already-resolved instance.
        $this->logFile = storage_path('logs/'.uniqid('test-audit-', true).'.log');
        config(['logging.audit_channel' => 'audit']);
        config(['logging.channels.audit' => [
            'driver' => 'single',
            'path' => $this->logFile,
            'level' => 'info',
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ]]);
        app('log')->forgetChannel('audit');
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            @unlink($this->logFile);
        }

        parent::tearDown();
    }

    private function trail(): string
    {
        return is_file($this->logFile) ? (string) file_get_contents($this->logFile) : '';
    }

    public function test_an_audited_mutation_is_mirrored_off_the_database(): void
    {
        // A role change is audited (User::$auditOnly = role/school_id/status).
        $this->actingAs($this->actor);
        $this->actor->update(['role' => 'registrar']);

        $audit = AuditLog::where('auditable_type', User::class)
            ->where('event', 'updated')
            ->firstOrFail();

        $trail = $this->trail();
        $this->assertStringContainsString('audit.data', $trail, 'The off-DB file must carry a data-audit line.');
        $this->assertStringContainsString('"auditable_type":"'.addslashes(User::class).'"', $trail);
        $this->assertStringContainsString('"event":"updated"', $trail);
        $this->assertStringContainsString('"id":'.$audit->id, $trail, 'The mirror line references the DB row id.');
        // Before/after values travel with the mirror.
        $this->assertStringContainsString('registrar', $trail);
    }

    public function test_login_events_are_mirrored_off_the_database(): void
    {
        event(new Login('web', $this->actor, false));
        event(new Failed('web', $this->actor, ['email' => $this->actor->email]));

        // DB rows still written (source of truth).
        $this->assertSame(1, LoginLog::where('event', LoginLog::EVENT_SUCCESS)->count());
        $this->assertSame(1, LoginLog::where('event', LoginLog::EVENT_FAILED)->count());

        $trail = $this->trail();
        $this->assertStringContainsString('audit.auth', $trail);
        $this->assertStringContainsString('"event":"'.LoginLog::EVENT_SUCCESS.'"', $trail);
        $this->assertStringContainsString('"event":"'.LoginLog::EVENT_FAILED.'"', $trail);
        $this->assertStringContainsString($this->actor->email, $trail);
    }

    public function test_a_rolled_back_mutation_leaves_no_phantom_mirror_line(): void
    {
        $this->actingAs($this->actor);

        DB::beginTransaction();
        $this->actor->update(['role' => 'admin']); // fires the audit + queues the mirror
        DB::rollBack();

        // The audit row rolled back with the change, and so must the mirror —
        // this is why the mirror is emitted via DB::afterCommit, not eagerly.
        $this->assertSame(0, AuditLog::where('auditable_type', User::class)->where('event', 'updated')->count());
        $this->assertStringNotContainsString('audit.data', $this->trail(), 'A rolled-back change must not appear in the off-DB trail.');
    }

    public function test_a_committed_mutation_survives_when_the_db_rows_are_wiped(): void
    {
        // The whole point of S4: after the attacker truncates the table, the
        // off-database copy is still there.
        $this->actingAs($this->actor);
        $this->actor->update(['role' => 'teacher']);

        AuditLog::query()->delete(); // simulate a DB-level wipe of the trail

        $this->assertSame(0, AuditLog::count());
        $this->assertStringContainsString('audit.data', $this->trail(), 'The off-DB trail survives a table wipe.');
        $this->assertStringContainsString('teacher', $this->trail());
    }
}
