<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Office;

class OfficeSeeder extends Seeder
{
    public function run()
    {
        $offices = [
            ['name' => 'Students'],
            ['name' => 'Faculty'],
            ['name' => 'Registrar'],
            ['name' => 'Accounting'],
            ['name' => 'Human Resources'],
            ['name' => 'Administration'],
            ['name' => 'Guidance Office'],
            ['name' => 'IT Department'],
            ['name' => 'Library'],
            ['name' => 'Trainee'],
            ['name' => 'Trainor'],
        ];

        foreach ($offices as $office) {
            Office::create([
                'school_id' => 1, // adjust if needed
                'name' => $office['name']
            ]);
        }
    }
}