<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Section;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->q;
        $results = [];

        // USERS
        $users = User::where(function ($query) use ($q) {
            $query->where('first_name', 'like', "%$q%")
                ->orWhere('middle_name', 'like', "%$q%")
                ->orWhere('last_name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$q%"])
                ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%$q%"]);
        })
        ->limit(5)
        ->get()
        ->map(function ($user) {
            return [
                'title' => $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name,
                'subtitle' => $user->email,
                'link' => route('settings.users.show', ['user' => $user->id])
            ];
        });

        if ($users->count()) {
            $results[] = [
                'type' => 'Users',
                'items' => $users
            ];
        }

        // STUDENTS
        $students = Student::where('first_name', 'like', "%$q%")
            ->orWhere('last_name', 'like', "%$q%")
            ->limit(5)
            ->get()
            ->map(function ($student) {
                return [
                    'title' => $student->first_name . ' ' . $student->last_name,
                    'subtitle' => 'Student',
                    'link' => '/students/' . $student->id
                ];
            });

        if ($students->count()) {
            $results[] = [
                'type' => 'Students',
                'items' => $students
            ];
        }

        // SUBJECTS
        $subjects = Subject::where('name', 'like', "%$q%")
            ->orWhere('code', 'like', "%$q%")
            ->limit(5)
            ->get()
            ->map(function ($subject) {
                return [
                    'title' => $subject->name,
                    'subtitle' => 'Subject • ' . $subject->code,
                    'link' => '/subjects/' . $subject->id
                ];
            });

        if ($subjects->count()) {
            $results[] = [
                'type' => 'Subjects',
                'items' => $subjects
            ];
        }

        // SECTIONS
        $sections = Section::where('name', 'like', "%$q%")
            ->limit(5)
            ->get()
            ->map(function ($section) {
                return [
                    'title' => $section->name,
                    'subtitle' => 'Section',
                    'link' => '/sections/' . $section->id
                ];
            });

        if ($sections->count()) {
            $results[] = [
                'type' => 'Sections',
                'items' => $sections
            ];
        }

        return response()->json($results);
    }
}