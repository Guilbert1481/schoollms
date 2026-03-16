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
                'school_name' => 'Memory Ridge International Schools',
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
                'email' => 'superadmin@lms.test',
            ],
            [
                'name' => 'System Owner',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
                'school_id' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Create School Admin
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            [
                'email' => 'admin@memoryridge.test',
            ],
            [
                'name' => 'School Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'school_id' => $school->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Create Admission Manager
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            [
                'email' => 'admission@memoryridge.test',
            ],
            [
                'name' => 'Admission Manager',
                'password' => Hash::make('password123'),
                'role' => 'admission',
                'school_id' => $school->id,
            ]
        );


        User::firstOrCreate(
            [
                'email' => 'vp.academics@schoollms.test',
            ],
            [
                'name' => 'VP Academics',
                'password' => Hash::make('password'),
                'role' => 'academics',
                'school_id' => $school->id,
            ]
        );
    }
}
