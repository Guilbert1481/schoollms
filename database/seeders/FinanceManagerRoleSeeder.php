<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Seeds the Finance Manager role for every school so it appears in the
 * Admin → User Management role dropdown.
 *
 * Idempotent.
 */
class FinanceManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $schoolIds = School::query()->pluck('id');

        if ($schoolIds->isEmpty()) {
            $schoolIds = collect([1]);
        }

        foreach ($schoolIds as $schoolId) {
            Role::query()->firstOrCreate(
                ['school_id' => $schoolId, 'name' => 'finance_manager'],
                []
            );
        }
    }
}
