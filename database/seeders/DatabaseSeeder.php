<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ProfessionalEducation\AssessmentOfLearningSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InitialSetupSeeder::class,
            ModuleSeeder::class,
            EnrollmentTypeSeeder::class,
            OfficeTypeSeeder::class,
            OfficeSeeder::class,
            TrainingTypeSeeder::class,
            SchoolProfileSeeder::class,
            QuoteSeeder::class,
            AcadEnrolmentRolesSeeder::class,

            AssessmentOfLearningSeeder::class,
        ]);
    }

}
