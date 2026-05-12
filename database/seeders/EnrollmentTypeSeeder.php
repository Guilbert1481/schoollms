<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('enrollment_types')->updateOrInsert(
            ['code' => 'REG'],
            ['name' => 'Regular', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('enrollment_types')->updateOrInsert(
            ['code' => 'SPL'],
            ['name' => 'Special', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}