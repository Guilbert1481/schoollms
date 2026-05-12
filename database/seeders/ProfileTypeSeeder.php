<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('profile_types')->insert([
            ['code' => 'student', 'name' => 'Academic Student'],
            ['code' => 'trainee', 'name' => 'Training / Seminar'],
            ['code' => 'teacher', 'name' => 'Teacher'],
            ['code' => 'employee', 'name' => 'Employee'],
            ['code' => 'guardian', 'name' => 'Guardian'],
            ['code' => 'alumni', 'name' => 'Alumni'],
            ['code' => 'admin', 'name' => 'Administrator'],
        ]);
    }
}