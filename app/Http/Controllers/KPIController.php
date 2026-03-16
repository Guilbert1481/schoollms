<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;

class KPIController extends Controller
{
    public function index()
    {
        // Example queries (adjust to your models)

        $activeTeachers = Teacher::where('status', 'active')->count();
        $enrolledStudents = Student::where('status', 'enrolled')->count();
        $totalUsers = User::count();

        $cards = [
            [
                'title' => 'Active Teachers',
                'value' => $activeTeachers,
                'icon'  => 'briefcase',
                'color' => 'blue',
            ],
            [
                'title' => 'Enrolled Students',
                'value' => $enrolledStudents,
                'icon'  => 'users',
                'color' => 'indigo',
            ],
            [
                'title' => 'Total Users',
                'value' => $totalUsers,
                'icon'  => 'user',
                'color' => 'green',
            ],
        ];

        return view('admin.dashboard', compact('cards'));
    }
}
