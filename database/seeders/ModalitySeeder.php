<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModalitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Face-to-face',        'code' => 'f2f'],
            ['name' => 'Online',              'code' => 'online'],
            ['name' => 'Hybrid',              'code' => 'hybrid'],
            ['name' => 'Asynchronous Online', 'code' => 'async_online'],
        ];

        foreach ($rows as $row) {
            DB::table('modalities')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}