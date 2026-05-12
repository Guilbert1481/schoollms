<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolProfile;
use App\Models\Trainee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function home(Request $request, string $schoolSlug)
    {
        return view('website.home', $this->viewData($request, $schoolSlug, 'home', 'Home'));
    }

    public function about(Request $request, string $schoolSlug)
    {
        return view('website.about', $this->viewData($request, $schoolSlug, 'about', 'About'));
    }

    public function programs(Request $request, string $schoolSlug)
    {
        return view('website.programs', $this->viewData($request, $schoolSlug, 'programs', 'Programs'));
    }

    public function courses(Request $request, string $schoolSlug)
    {
        return view('website.courses', $this->viewData($request, $schoolSlug, 'courses', 'Courses'));
    }

    public function admissions(Request $request, string $schoolSlug)
    {
        return view('website.admissions', $this->viewData($request, $schoolSlug, 'admissions', 'Admissions'));
    }

    public function blog(Request $request, string $schoolSlug)
    {
        return view('website.blog', $this->viewData($request, $schoolSlug, 'blog', 'Blog'));
    }

    public function traineeLogin(Request $request, string $schoolSlug)
    {
        $school = $this->resolveSchool($request, $schoolSlug);

        $credentials = $request->validateWithBag('login', [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $loginUser = User::where('school_id', $school->id)
            ->where('email', $credentials['email'])
            ->first();

        if ($loginUser && strtolower((string) $loginUser->role) !== 'trainee') {
            return back()
                ->withErrors([
                    'email' => 'This account belongs to a non-trainee role. Use the regular school login page.',
                ], 'login')
                ->withInput($request->except('password'))
                ->with('openEnrollModal', true)
                ->with('enrollTab', 'login');
        }

        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'school_id' => $school->id,
            'role' => 'trainee',
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'Invalid trainee credentials for this school.',
                ], 'login')
                ->withInput($request->except('password'))
                ->with('openEnrollModal', true)
                ->with('enrollTab', 'login');
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function traineeSignup(Request $request, string $schoolSlug)
    {
        $school = $this->resolveSchool($request, $schoolSlug);

        $validated = $request->validateWithBag('signup', [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {
            $isTrainee = strtolower((string) $existingUser->role) === 'trainee'
                || (bool) optional(optional($existingUser->profile)->trainee)->id;

            $errorBag = $isTrainee ? 'login' : 'signup';

            return back()
                ->withErrors([
                    'email' => $isTrainee
                        ? 'A trainee account with this email already exists. Please log in instead.'
                        : 'This email is already used by another account.',
                ], $errorBag)
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('openEnrollModal', true)
                ->with('enrollTab', $isTrainee ? 'login' : 'signup');
        }

            $user = null;

        DB::transaction(function () use ($validated, $school, &$user) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'school_id' => $school->id,
                'role' => 'trainee',
                'phone' => $validated['contact_number'] ?? null,
                'email_verified_at' => now(),
            ]);

            $profileId = DB::table('profiles')->insertGetId([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'contact_number' => $validated['contact_number'] ?? null,
                'profile_type' => 'trainee',
                'school_id' => $school->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Trainee::create([
                'profile_id' => $profileId,
                'status' => 'active',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    private function viewData(Request $request, string $schoolSlug, string $activePage, string $pageTitle): array
    {
        $school = $this->resolveSchool($request, $schoolSlug);

        $profile = SchoolProfile::where('school_id', $school->id)->first();
        $resolvedSlug = $school->slug ?: $schoolSlug;

        return [
            'pageTitle' => $pageTitle,
            'activePage' => $activePage,
            'schoolSlug' => $resolvedSlug,
            'schoolName' => $profile?->school_name ?: ($school->school_name ?: 'School Portal'),
            'schoolLogo' => $this->logoUrl($profile?->school_logo),
            'schoolHero' => $this->logoUrl($profile?->school_hero),
            'missionStatement' => $profile?->mission ?: 'To provide a transformative educational experience that fosters critical thinking and technological excellence.',
            'visionStatement' => $profile?->vision ?: 'To be a premier global institution recognized for innovation and community impact.',
            'schoolMotto' => $profile?->motto,
            'loginUrl' => Route::has('school.login') ? route('school.login', ['slug' => $resolvedSlug]) : url('/login'),
        ];
    }

    private function resolveSchool(Request $request, string $schoolSlug): School
    {
        $school = School::where('slug', $schoolSlug)->first();

        if (!$school && DB::getSchemaBuilder()->hasColumn('schools', 'domain')) {
            $host = $request->getHost();
            $school = School::where('domain', $host)
                ->orWhere('domain', 'www.' . $host)
                ->first();
        }

        abort_if(!$school, 404, 'School not found.');

        return $school;
    }

    private function logoUrl(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://') || str_starts_with($logoPath, 'data:')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, '/')) {
            return $logoPath;
        }

        return Storage::url($logoPath);
    }
}
