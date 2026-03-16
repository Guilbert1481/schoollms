<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

class ModuleDependencySeeder extends Seeder
{
    public function run()
    {
        $dependencies = [

            // -------------------------
            // Academic Core
            // -------------------------
            'Grades' => ['Students', 'Classes & Scheduling'],
            'Assessments' => ['Students', 'Teachers', 'Classes & Scheduling'],
            'Attendance' => ['Students', 'Classes & Scheduling'],
            'Lessons / Curriculum' => ['Teachers', 'Classes & Scheduling'],
            'Learning Outcomes / Competency Tracking' => ['Lessons / Curriculum', 'Assessments'],

            // -------------------------
            // Admissions & Enrollment
            // -------------------------
            'Enrollment' => ['Registration', 'Students'],
            'Admissions' => ['Registration'],

            // -------------------------
            // HR
            // -------------------------
            'Payroll' => ['HR Management'],
            'Staff Scheduling' => ['Teachers', 'HR Management'],
            'Performance & Evaluation' => ['Teachers', 'HR Management'],

            // -------------------------
            // Finance
            // -------------------------
            'Invoicing / Billing' => ['Accounting'],
            'Financial Aid' => ['Students', 'Accounting'],
            'Procurement' => ['Accounting'],

            // -------------------------
            // Student Services
            // -------------------------
            'Registrar' => ['Students', 'Grades', 'Classes & Scheduling'],
            'Career Services' => ['Students'],
            'Alumni Management' => ['Students'],
            'Parent Portal Enhancements' => ['Students', 'Grades', 'Attendance'],

            // -------------------------
            // Operations
            // -------------------------
            'Dormitory / Housing Management' => ['Students'],
            'Cafeteria / Meal Plans' => ['Students'],
            'Transport' => ['Students'],
            'Security' => ['Students', 'Staff Scheduling'],
            'Facilities Management' => ['Inventory'],
            'Inventory' => ['Procurement'],

            // -------------------------
            // Research
            // -------------------------
            'Research Grants Management' => ['Research'],

            // -------------------------
            // Assessment Technology
            // -------------------------
            'Online Exam Proctoring' => ['Assessments', 'Students', 'Teachers'],

            // -------------------------
            // Communications
            // -------------------------
            'Announcements' => ['Students', 'Teachers'],
            'Events Management' => ['Students', 'Teachers'],
            'Marketing & Communications' => ['Events Management'],

            // -------------------------
            // System
            // -------------------------
            'Analytics & Reports' => ['Students', 'Grades', 'Attendance'],
            'Logs & Audit Trail' => ['Permissions & Roles'],
        ];

        foreach ($dependencies as $moduleName => $requiredModules) {

            $module = Module::where('name', $moduleName)->first();

            if (!$module) continue;

            foreach ($requiredModules as $dependencyName) {

                $dependency = Module::where('name', $dependencyName)->first();

                if ($dependency) {
                    DB::table('module_dependencies')->updateOrInsert([
                        'module_id' => $module->id,
                        'depends_on_module_id' => $dependency->id,
                    ]);
                }
            }
        }
    }
}
