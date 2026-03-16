<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;
use App\Models\School;
use App\Models\User;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed configurations for ALL SCHOOLS
        $schools = School::all();

        if ($schools->isEmpty()) {
            $this->command->warn('⚠️  No schools found in database.');
        } else {
            foreach ($schools as $school) {
                $this->seedConfigurationsForOwner($school);
                $this->command->info("✅ Configurations seeded for School: {$school->school_name}");
            }
        }

        // 2. Seed configurations for FREELANCE TEACHERS (users without school_id)
        $freelanceTeachers = User::whereNull('school_id')
            ->where('role', 'teacher') // Adjust this based on your role column
            ->get();

        if ($freelanceTeachers->isEmpty()) {
            $this->command->warn('⚠️  No freelance teachers found.');
        } else {
            foreach ($freelanceTeachers as $teacher) {
                $this->seedConfigurationsForOwner($teacher);
                $this->command->info("✅ Configurations seeded for Freelance Teacher: {$teacher->name}");
            }
        }

        $this->command->info('🎉 All configurations seeded successfully!');
    }

    private function seedConfigurationsForOwner($owner)
    {
        // Clear existing configurations for this owner
        Configuration::where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->delete();

        $ownerType = get_class($owner);
        $ownerId = $owner->id;

        // Academic Levels
        $academicLevels = [
            ['label' => 'General', 'value' => 'general', 'order' => 1],
            ['label' => 'Elementary', 'value' => 'elementary', 'order' => 2],
            ['label' => 'Junior High School', 'value' => 'junior_high_school', 'order' => 3],
            ['label' => 'Senior High School', 'value' => 'senior_high_school', 'order' => 4],
            ['label' => 'College', 'value' => 'college', 'order' => 5],
            ['label' => 'Masteral', 'value' => 'masteral', 'order' => 6],
            ['label' => 'Doctoral', 'value' => 'doctoral', 'order' => 7],
        ];

        foreach ($academicLevels as $level) {
            Configuration::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'category' => 'academic_level',
                'label' => $level['label'],
                'value' => $level['value'],
                'is_active' => true,
                'order_index' => $level['order'],
            ]);
        }

        // Difficulty Levels
        $difficulties = [
            ['label' => 'Average', 'value' => 'average', 'order' => 1],
            ['label' => 'Advanced', 'value' => 'advanced', 'order' => 2],
        ];

        foreach ($difficulties as $difficulty) {
            Configuration::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'category' => 'difficulty',
                'label' => $difficulty['label'],
                'value' => $difficulty['value'],
                'is_active' => true,
                'order_index' => $difficulty['order'],
            ]);
        }

        // Question Types
        $questionTypes = [
            ['label' => 'Multiple Choice', 'value' => 'multiple_choice', 'order' => 1],
            ['label' => 'True / False', 'value' => 'true_or_false', 'order' => 2],
            ['label' => 'Modified True / False', 'value' => 'modified_true_or_false', 'order' => 3],
            ['label' => 'Identification', 'value' => 'identification', 'order' => 4],
            ['label' => 'Fill in the Blank', 'value' => 'fib', 'order' => 5],
            ['label' => 'Matching Type', 'value' => 'matching', 'order' => 6],
            ['label' => 'Enumeration', 'value' => 'enumeration', 'order' => 7],
            ['label' => 'Essay', 'value' => 'essay', 'order' => 8],
        ];

        foreach ($questionTypes as $type) {
            Configuration::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'category' => 'question_type',
                'label' => $type['label'],
                'value' => $type['value'],
                'is_active' => true,
                'order_index' => $type['order'],
            ]);
        }

        // Assessment Types
        $assessmentTypes = [
            ['label' => 'Seatwork', 'value' => 'seatwork', 'order' => 1],
            ['label' => 'Quiz', 'value' => 'quiz', 'order' => 2],
            ['label' => 'Homework', 'value' => 'homework', 'order' => 3],
            ['label' => 'Long Test', 'value' => 'long_test', 'order' => 4],
            ['label' => 'Evaluation Test', 'value' => 'evaluation_test', 'order' => 5],
            ['label' => 'Diagnostic Test', 'value' => 'diagnostic_test', 'order' => 6],
            ['label' => 'Prelim Exam', 'value' => 'prelim_exam', 'order' => 7],
            ['label' => 'Midterm Exam', 'value' => 'midterm_exam', 'order' => 8],
            ['label' => 'Final Exam', 'value' => 'final_exam', 'order' => 9],
            ['label' => 'Review', 'value' => 'review', 'order' => 10],
            ['label' => 'Practice', 'value' => 'practice', 'order' => 11],
            ['label' => 'Mock Test', 'value' => 'mock_test', 'order' => 12],
        ];

        foreach ($assessmentTypes as $type) {
            Configuration::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'category' => 'assessment_type',
                'label' => $type['label'],
                'value' => $type['value'],
                'is_active' => true,
                'order_index' => $type['order'],
            ]);
        }

        // Term Divisions
        $termDivisions = [
            ['label' => 'Prelim', 'value' => 'prelim', 'order' => 1],
            ['label' => 'Midterm', 'value' => 'midterm', 'order' => 2],
            ['label' => 'Pre-Finals', 'value' => 'pre_finals', 'order' => 3],
            ['label' => 'Finals', 'value' => 'finals', 'order' => 4],
        ];

        foreach ($termDivisions as $division) {
            Configuration::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'category' => 'term_division',
                'label' => $division['label'],
                'value' => $division['value'],
                'is_active' => true,
                'order_index' => $division['order'],
            ]);
        }
    }
}