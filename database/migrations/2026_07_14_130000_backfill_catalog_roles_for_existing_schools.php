<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The create_school_roles migration backfilled the catalog as it stood when
     * it ran. The catalog has since grown (office_head, hr_manager,
     * subject_coordinator), so top up every existing school with any catalog
     * role it is missing. Idempotent: rows that already exist are ignored.
     *
     * New schools created after this point pick their own roles at registration
     * — this backfill only covers schools that predate the catalog additions.
     */
    public function up(): void
    {
        $catalogKeys = array_keys((array) config('roles.catalog', []));

        if ($catalogKeys === []) {
            return;
        }

        $now = now();

        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            $rows = array_map(fn ($key) => [
                'school_id' => $schoolId,
                'role_key' => $key,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $catalogKeys);

            DB::table('school_roles')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // No-op: we cannot tell which rows this backfill added versus which the
        // superadmin chose, so we leave the subscriptions untouched on rollback.
    }
};
