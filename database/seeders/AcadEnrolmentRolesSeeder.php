<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Seeds the academic-enrolment approval roles for every school:
 *   - principal           (Basic Ed approver)
 *   - subject_coordinator (SHS approver / strand-level)
 *
 * Idempotent: safe to re-run.
 */
class AcadEnrolmentRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'principal'           => 'Principal',
            'subject_coordinator' => 'Subject Coordinator',
        ];

        $schoolIds = School::query()->pluck('id');

        if ($schoolIds->isEmpty()) {
            // Fall back to the conventional default tenant so dev environments
            // still get the seeded roles even before any School row exists.
            $schoolIds = collect([1]);
        }

        foreach ($schoolIds as $schoolId) {
            foreach ($roles as $name => $label) {
                Role::query()->firstOrCreate(
                    ['school_id' => $schoolId, 'name' => $name],
                    []
                );
            }
        }
    }
}
