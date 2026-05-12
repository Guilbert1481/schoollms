<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingType;

class TrainingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1; // ⚠️ change if needed

        $types = [
            [
                'name' => 'Seminar',
                'code' => 'SEM',
                'description' => 'Short formal training sessions or lectures'
            ],
            [
                'name' => 'Workshop',
                'code' => 'WRK',
                'description' => 'Hands-on training sessions'
            ],
            [
                'name' => 'Conference',
                'code' => 'CONF',
                'description' => 'Large formal gatherings with multiple sessions'
            ],
            [
                'name' => 'Certification Program',
                'code' => 'CERT',
                'description' => 'Programs leading to certification'
            ],
            [
                'name' => 'Review Program',
                'code' => 'REV',
                'description' => 'Review classes for board or licensure exams'
            ],
            [
                'name' => 'Short Course',
                'code' => 'SC',
                'description' => 'Short-term structured learning programs'
            ],
            [
                'name' => 'Bootcamp',
                'code' => 'BOOT',
                'description' => 'Intensive training programs'
            ],
        ];

        foreach ($types as $type) {
            TrainingType::updateOrCreate(
                [
                    'name' => $type['name'],
                    'school_id' => $schoolId
                ],
                [
                    'code' => $type['code'],
                    'description' => $type['description']
                ]
            );
        }
    }
}