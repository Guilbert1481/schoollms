<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;  
use App\Models\SchoolSetting;
use App\Models\School;

class SchoolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            SchoolSetting::firstOrCreate(
                ['school_id' => $school->id],
                [
                    'academic_levels' => [
                        'college',
                        'senior_high_school'
                    ],
                    'question_types' => [
                        'multiple_choice',
                        'essay'
                    ],
                    'difficulty_levels' => [
                        'average',
                        'advanced'
                    ],
                    'assessment_types' => [
                        'quiz',
                        'long_test',
                        'final_exam'
                    ],
                ]
            );
        }
    }
}
