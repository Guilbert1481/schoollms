<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run()
    {
        $modules = [

            // -------------------------
            // Admissions & Enrollment
            // -------------------------
            ['name' => 'Registration', 'slug' => 'registration', 'category' => 'Admissions'],
            ['name' => 'Admissions', 'slug' => 'admissions', 'category' => 'Admissions'],
            ['name' => 'Enrollment', 'slug' => 'enrollment', 'category' => 'Admissions'],

            // -------------------------
            // Academic Core
            // -------------------------
            ['name' => 'Academics', 'slug' => 'academics', 'category' => 'Academic Core'],
            ['name' => 'Students', 'slug' => 'students', 'category' => 'Academic Core'],
            ['name' => 'Teachers', 'slug' => 'teachers', 'category' => 'Academic Core'],
            ['name' => 'Classes & Scheduling', 'slug' => 'classes-scheduling', 'category' => 'Academic Core'],
            ['name' => 'Lessons / Curriculum', 'slug' => 'lessons-curriculum', 'category' => 'Academic Core'],
            ['name' => 'Assessments', 'slug' => 'assessments', 'category' => 'Academic Core'],
            ['name' => 'Grades', 'slug' => 'grades', 'category' => 'Academic Core'],
            ['name' => 'Attendance', 'slug' => 'attendance', 'category' => 'Academic Core'],
            ['name' => 'Learning Outcomes / Competency Tracking', 'slug' => 'learning-outcomes', 'category' => 'Academic Core'],

            // -------------------------
            // HR & Staff
            // -------------------------
            ['name' => 'HR Management', 'slug' => 'hr-management', 'category' => 'HR'],
            ['name' => 'Payroll', 'slug' => 'payroll', 'category' => 'HR'],
            ['name' => 'Staff Scheduling', 'slug' => 'staff-scheduling', 'category' => 'HR'],
            ['name' => 'Performance & Evaluation', 'slug' => 'performance-evaluation', 'category' => 'HR'],

            // -------------------------
            // Communications
            // -------------------------
            ['name' => 'Announcements', 'slug' => 'announcements', 'category' => 'Communications'],
            ['name' => 'Marketing & Communications', 'slug' => 'marketing-communications', 'category' => 'Communications'],
            ['name' => 'Events Management', 'slug' => 'events-management', 'category' => 'Communications'],

            // -------------------------
            // System & Administration
            // -------------------------
            ['name' => 'Permissions & Roles', 'slug' => 'permissions-roles', 'category' => 'System'],
            ['name' => 'File Management', 'slug' => 'file-management', 'category' => 'System'],
            ['name' => 'Logs & Audit Trail', 'slug' => 'logs-audit', 'category' => 'System'],
            ['name' => 'Analytics & Reports', 'slug' => 'analytics-reports', 'category' => 'System'],
            ['name' => 'Accreditation Management', 'slug' => 'accreditation-management', 'category' => 'Administration'],

            // -------------------------
            // Finance
            // -------------------------
            ['name' => 'Accounting', 'slug' => 'accounting', 'category' => 'Finance'],
            ['name' => 'Invoicing / Billing', 'slug' => 'billing', 'category' => 'Finance'],
            ['name' => 'Financial Aid', 'slug' => 'financial-aid', 'category' => 'Finance'],
            ['name' => 'Procurement', 'slug' => 'procurement', 'category' => 'Finance'],

            // -------------------------
            // Student Services
            // -------------------------
            ['name' => 'Registrar', 'slug' => 'registrar', 'category' => 'Student Services'],
            ['name' => 'Student Affairs', 'slug' => 'student-affairs', 'category' => 'Student Services'],
            ['name' => 'Alumni Management', 'slug' => 'alumni-management', 'category' => 'Student Services'],
            ['name' => 'Career Services', 'slug' => 'career-services', 'category' => 'Student Services'],
            ['name' => 'Parent Portal Enhancements', 'slug' => 'parent-portal', 'category' => 'Student Services'],

            // -------------------------
            // Operations
            // -------------------------
            ['name' => 'Facilities Management', 'slug' => 'facilities-management', 'category' => 'Operations'],
            ['name' => 'Inventory', 'slug' => 'inventory', 'category' => 'Operations'],
            ['name' => 'Security', 'slug' => 'security', 'category' => 'Operations'],
            ['name' => 'Transport', 'slug' => 'transport', 'category' => 'Operations'],
            ['name' => 'Dormitory / Housing Management', 'slug' => 'dormitory-housing', 'category' => 'Operations'],
            ['name' => 'Cafeteria / Meal Plans', 'slug' => 'cafeteria-meal-plans', 'category' => 'Operations'],

            // -------------------------
            // Library & Research
            // -------------------------
            ['name' => 'Library', 'slug' => 'library', 'category' => 'Library'],
            ['name' => 'Research', 'slug' => 'research', 'category' => 'Research'],
            ['name' => 'Research Grants Management', 'slug' => 'research-grants', 'category' => 'Research'],

            // -------------------------
            // Assessment Technology
            // -------------------------
            ['name' => 'Online Exam Proctoring', 'slug' => 'online-exam-proctoring', 'category' => 'Assessments'],
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
