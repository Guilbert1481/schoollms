<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * QR-code landing page for the public enrolment wizard.
 *
 * Flow:
 *   1. Student scans the QR code (printed/displayed by Admission Manager).
 *   2. They land on /apply/qr/{term}.
 *      - Logged in as a student      → redirect straight to the wizard.
 *      - Logged in as another role   → friendly error (logout suggested).
 *      - Not logged in               → show a small dual-purpose card with a
 *        "Sign in" form and a "Create an account" mini form. Either path
 *        ends with auth()->login() and redirect to the wizard.
 */
class EnrollmentQrLandingController extends Controller
{
    public function show($termId)
    {
        $term = Term::findOrFail($termId);
        $user = Auth::user();

        if ($user) {
            if ($user->role === 'student') {
                return redirect()->route('public.apply.show', $term->id);
            }

            return view('public.enrollment-qr-landing', [
                'term'           => $term,
                'wrongRole'      => true,
            ]);
        }

        return view('public.enrollment-qr-landing', [
            'term'      => $term,
            'wrongRole' => false,
        ]);
    }

    public function login(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('public.apply.qr', $term->id)
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $credentials = $validator->validated();

        if (! Auth::attempt($credentials, true)) {
            return redirect()
                ->route('public.apply.qr', $term->id)
                ->withErrors(['email' => 'Invalid credentials.'])
                ->withInput(['email' => $credentials['email']]);
        }

        $request->session()->regenerate();

        if (Auth::user()->role !== 'student') {
            Auth::logout();
            return redirect()
                ->route('public.apply.qr', $term->id)
                ->withErrors(['email' => 'Only student accounts can fill out the enrolment form.']);
        }

        return redirect()->route('public.apply.show', $term->id);
    }

    public function register(Request $request, $termId)
    {
        $term = Term::findOrFail($termId);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name'  => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email|max:191|unique:users,email',
            'password'    => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('public.apply.qr', $term->id)
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $data = $validator->validated();

        $user = User::create([
            'first_name'  => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name'   => $data['last_name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => 'student',
            'school_id'   => $term->school_id,
        ]);

        Student::create([
            'user_id'        => $user->id,
            'school_id'      => $term->school_id,
            'student_number' => $this->generateStudentNumber(),
            'first_name'     => $data['first_name'],
            'middle_name'    => $data['middle_name'] ?? null,
            'last_name'      => $data['last_name'],
            'email'          => $data['email'],
        ]);

        $profileId = DB::table('profiles')->insertGetId([
            'user_id'      => $user->id,
            'school_id'    => $term->school_id,
            'profile_type' => 'student',
            'first_name'   => $data['first_name'],
            'middle_name'  => $data['middle_name'] ?? null,
            'last_name'    => $data['last_name'],
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Ensure a "student" role row exists for this school, then link via
        // account_access so the User Management page recognises the role.
        $role = DB::table('roles')
            ->where('school_id', $term->school_id)
            ->where('name', 'student')
            ->first();

        $roleId = $role->id ?? DB::table('roles')->insertGetId([
            'school_id'  => $term->school_id,
            'name'       => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_access')->insert([
            'user_id'       => $user->id,
            'role_id'       => $roleId,
            'office_id'     => null,
            'person_id'     => $profileId,
            'role_snapshot' => 'Student',
            'start_date'    => now(),
            'assigned_by'   => $user->id,
            'remarks'       => 'Self-registered via enrolment QR code',
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('public.apply.show', $term->id);
    }

    protected function generateStudentNumber(): string
    {
        $year = now()->format('Y');
        do {
            $candidate = sprintf('S-%s-%06d', $year, random_int(1, 999999));
        } while (Student::where('student_number', $candidate)->exists());

        return $candidate;
    }
}
