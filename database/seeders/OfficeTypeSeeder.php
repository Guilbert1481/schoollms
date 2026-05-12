<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('office_types')->insert([
            ['name' => 'Academic', 'code' => 'ACA'],
            ['name' => 'Administrative', 'code' => 'ADM'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Human Resource', 'code' => 'HR'],
            ['name' => 'Training', 'code' => 'TRN'],
            ['name' => 'External', 'code' => 'EXT'],
            ['name' => 'System', 'code' => 'SYS'],
        ]);
    }
}