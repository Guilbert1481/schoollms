<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Create School
        |--------------------------------------------------------------------------
        */

        $school = School::firstOrCreate(
            ['slug' => 'memory-ridge'],
            [
                'school_name' => 'Memory Ridge International Schools and Colleges',
                'code' => 'MRIS',
                'domain' => 'memoryridge.local',
                'type' => 'school',
                'plan_name' => 'Premium',
                'is_active' => true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 2. Create Superadmin (System Owner)
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            [
                'email' => 'superadmin@sophentis.edu.ph',
            ],
            [
                'first_name' => 'Guilbert',
                'middle_name' => 'Llantos',
                'last_name' => 'Jabinar',
                'email' => 'admin@system.com', // ⚠️ add if required
                'password' => Hash::make('123456789'),
                'role' => 'superadmin',
                'school_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Create School Admin
        |--------------------------------------------------------------------------
        */
        
        User::firstOrCreate(
            [
                'email' => 'admin@memoryridge.edu.ph',
            ],
            [
                'first_name' => 'Guilbert',
                'middle_name' => 'Llantos',
                'last_name' => 'Jabinar',
                'email' => 'admin@memoryridge.edu.ph', // ⚠️ add if required
                'password' => Hash::make('123456789'),
                'role' => 'admin',
                'school_id' => $school->id,
            ]
        );

    }
}
