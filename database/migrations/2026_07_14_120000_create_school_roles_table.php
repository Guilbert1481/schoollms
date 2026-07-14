<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-school role subscriptions. Mirrors `school_modules`: which roles from
     * the config/roles.php catalog a school is entitled to assign users to.
     */
    public function up(): void
    {
        Schema::create('school_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');

            // Canonical users.role string (snake_case), e.g. 'dean', 'principal'.
            $table->string('role_key');

            // The toggle switch.
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            // Each school has at most one entry per role.
            $table->unique(['school_id', 'role_key']);
        });

        // Backfill: enable every catalog role for all pre-existing schools.
        // (The two seeded test schools are intended to have all roles; any
        // other school already created before this feature keeps working.)
        $catalogKeys = array_keys((array) config('roles.catalog', []));

        if ($catalogKeys !== []) {
            $now = now();

            foreach (DB::table('schools')->pluck('id') as $schoolId) {
                $rows = array_map(fn ($key) => [
                    'school_id' => $schoolId,
                    'role_key' => $key,
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $catalogKeys);

                // updateOrInsert-style guard via insertOrIgnore (unique key).
                DB::table('school_roles')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_roles');
    }
};
