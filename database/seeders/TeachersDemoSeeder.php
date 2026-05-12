<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\ProfileType;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\Scheduler\TeacherAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Creates 15 demo Teacher accounts wired through:
 *   users -> profiles -> teachers -> account_access
 * and seeds Mon–Fri teacher_availabilities so the Scheduler tool has data.
 *
 * Idempotent: safe to re-run; matches by email and existing FK rows.
 *
 * Run:
 *   php artisan db:seed --class=Database\\Seeders\\TeachersDemoSeeder
 */
class TeachersDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->orderBy('id')->first();
        if (! $school) {
            $this->command?->error('No school found. Run InitialSetupSeeder first.');
            return;
        }

        $teacherRole = Role::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'teacher'],
            ['authority_level' => 30]
        );

        $teacherProfileTypeId = ProfileType::query()->where('code', 'teacher')->value('id');

        $teachers = [
            ['Maria',    'Santos',     'Cruz',       'F', 'English'],
            ['Jose',     'Reyes',      'Dela Cruz',  'M', 'Mathematics'],
            ['Anna',     'Lopez',      'Garcia',     'F', 'Filipino'],
            ['Pedro',    'Bautista',   'Mendoza',    'M', 'Science'],
            ['Carmela',  'Villanueva', 'Ramos',      'F', 'Social Studies'],
            ['Ramon',    'Aquino',     'Torres',     'M', 'MAPEH'],
            ['Liza',     'Diaz',       'Navarro',    'F', 'Values Education'],
            ['Michael',  'Castillo',   'Domingo',    'M', 'TLE / ICT'],
            ['Grace',    'Pascual',    'Salazar',    'F', 'Literature'],
            ['Daniel',   'Tan',        'Uy',         'M', 'Physics'],
            ['Sophia',   'Marquez',    'Alvarez',    'F', 'Biology'],
            ['Joel',     'Gonzales',   'Ortega',     'M', 'Chemistry'],
            ['Patricia', 'Cruz',       'Lim',        'F', 'History'],
            ['Andres',   'Roxas',      'Soriano',    'M', 'Physical Education'],
            ['Bea',      'Fernandez',  'Estrada',    'F', 'Research'],
        ];

        // Subject-keyword map for teacher_subjects assignment.
        $subjectKeywords = [
            'English'            => ['ENG-', 'English', 'Linguistics', 'Grammar', 'Macroskills', 'Speech'],
            'Mathematics'        => ['Math', 'MMW'],
            'Filipino'           => ['FIL-', 'Filipino', 'Pagbasa'],
            'Science'            => ['Science', 'STS'],
            'Social Studies'     => ['History', 'Social', 'Contemporary World', 'RIZAL'],
            'MAPEH'              => ['PE-', 'Art', 'Music', 'Health'],
            'Values Education'   => ['Ethics', 'Values'],
            'TLE / ICT'          => ['Technology', 'TTL', 'NLAC'],
            'Literature'         => ['Literature', 'Mythology', 'Folklore', 'SEAL', 'SPLE', 'AFRO', 'CHILD'],
            'Physics'            => ['Physics'],
            'Biology'            => ['Biology', 'Bio'],
            'Chemistry'          => ['Chemistry', 'Chem'],
            'History'            => ['History', 'RIZAL', 'Philippine'],
            'Physical Education' => ['PE-', 'Physical Education', 'NSTP'],
            'Research'           => ['Research', 'Assessment'],
        ];

        $allSubjectIds = DB::table('subjects')
            ->when($school->id && Schema::hasColumn('subjects', 'school_id'), fn($q) => $q->where('school_id', $school->id))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($teachers, $school, $teacherRole, $teacherProfileTypeId, $subjectKeywords, $allSubjectIds) {
            $count = 1;
            foreach ($teachers as [$first, $middle, $last, $gender, $specialization]) {
                $email = sprintf(
                    '%s.%s@memoryridge.edu.ph',
                    strtolower($first),
                    strtolower(str_replace(' ', '', $last))
                );

                // 1. Users
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'school_id'  => $school->id,
                        'first_name' => $first,
                        'middle_name'=> $middle,
                        'last_name'  => $last,
                        'password'   => Hash::make('password123'),
                        'role'       => 'teacher',
                    ]
                );

                // 2. Profiles
                $profile = Profile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'profile_type'    => 'teacher',
                        'profile_code'    => 'TCH-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT),
                        'school_id'       => $school->id,
                        'status'          => 'active',
                        'first_name'      => $first,
                        'middle_name'     => $middle,
                        'last_name'       => $last,
                        'gender'          => $gender,
                        'birthday'        => Carbon::now()->subYears(28 + ($count % 12))->subDays($count * 7)->toDateString(),
                        'contact_number'  => '+63 917 ' . str_pad((string) (1000000 + $count), 7, '0', STR_PAD_LEFT),
                        'address'         => 'Brgy. ' . $count . ', Memory Ridge',
                        'city'            => 'Quezon City',
                        'province'        => 'Metro Manila',
                        'country'         => 'Philippines',
                        'nationality'     => 'Filipino',
                    ]
                );

                if ($teacherProfileTypeId && DB::getSchemaBuilder()->hasColumn('profiles', 'profile_type_id')) {
                    DB::table('profiles')
                        ->where('id', $profile->id)
                        ->whereNull('profile_type_id')
                        ->update(['profile_type_id' => $teacherProfileTypeId]);
                }

                // 3. Teachers
                $teacherId = DB::table('teachers')->where('profile_id', $profile->id)->value('id');
                if (! $teacherId) {
                    $teacherId = DB::table('teachers')->insertGetId([
                        'profile_id'         => $profile->id,
                        'employee_number'    => 'EMP-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT),
                        'rank'               => 'Instructor',
                        'employment_type'    => $count % 4 === 0 ? 'Part-time' : 'Full-time',
                        'employment_status'  => 'Active',
                        'hire_date'          => Carbon::now()->subYears(($count % 8) + 1)->toDateString(),
                        'specialization'     => $specialization,
                        'highest_degree'     => $count % 5 === 0 ? 'MA' : 'BS',
                        'teaching_load_limit'=> 24,
                        'adviser_flag'       => $count % 3 === 0,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                // 4. Account access
                $hasAccess = DB::table('account_access')
                    ->where('user_id', $user->id)
                    ->where('person_id', $profile->id)
                    ->exists();

                if (! $hasAccess) {
                    $row = [
                        'user_id'        => $user->id,
                        'person_id'      => $profile->id,
                        'start_date'     => Carbon::now()->toDateString(),
                        'is_active'      => 1,
                        'remarks'        => 'Seeded teacher demo account',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                    if (DB::getSchemaBuilder()->hasColumn('account_access', 'role_id')) {
                        $row['role_id'] = $teacherRole->id;
                    }
                    if (DB::getSchemaBuilder()->hasColumn('account_access', 'role_snapshot')) {
                        $row['role_snapshot'] = 'teacher';
                    }
                    DB::table('account_access')->insert($row);
                }

                // 5. Teacher profile (used by scheduler + teacher_subjects FK)
                $teacherProfileId = DB::table('teacher_profiles')->where('user_id', $user->id)->value('id');
                if (! $teacherProfileId) {
                    $teacherProfileId = DB::table('teacher_profiles')->insertGetId([
                        'user_id'           => $user->id,
                        'employee_id'       => 'EMP-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT),
                        'department'        => $specialization,
                        'specialization'    => $specialization,
                        'employment_status' => 'active',
                        'employment_start'  => Carbon::now()->subYears(($count % 8) + 1)->toDateString(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                // 6. Teacher subjects (match specialization keywords; fallback round-robin)
                $matchedSubjectIds = [];
                $keywords = $subjectKeywords[$specialization] ?? [];
                if (! empty($keywords) && Schema::hasTable('subjects')) {
                    $q = DB::table('subjects');
                    if (Schema::hasColumn('subjects', 'school_id')) $q->where('school_id', $school->id);
                    $q->where(function ($w) use ($keywords) {
                        foreach ($keywords as $kw) {
                            $w->orWhere('name', 'like', "%{$kw}%")
                              ->orWhere('code', 'like', "%{$kw}%");
                        }
                    });
                    $matchedSubjectIds = $q->limit(5)->pluck('id')->all();
                }

                if (empty($matchedSubjectIds) && ! empty($allSubjectIds)) {
                    // Fallback: assign 3 subjects round-robin so the teacher still appears in scheduler.
                    $total = count($allSubjectIds);
                    for ($i = 0; $i < 3; $i++) {
                        $matchedSubjectIds[] = $allSubjectIds[($count + $i) % $total];
                    }
                }

                foreach (array_unique($matchedSubjectIds) as $idx => $subjectId) {
                    DB::table('teacher_subjects')->updateOrInsert(
                        ['teacher_id' => $teacherProfileId, 'subject_id' => $subjectId],
                        [
                            'qualification_level' => $idx === 0 ? 'primary' : 'secondary',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                // 7. Teacher availabilities (Mon–Fri 08:00–17:00) keyed to teacher_profiles.id
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                foreach ($days as $day) {
                    TeacherAvailability::firstOrCreate(
                        [
                            'teacher_id'  => $teacherProfileId,
                            'day_of_week' => $day,
                            'start_time'  => '08:00:00',
                            'end_time'    => '17:00:00',
                        ],
                        ['is_available' => true]
                    );
                }

                $count++;
            }
        });

        $this->command?->info('Seeded 15 demo teachers with profiles, account_access, and Mon–Fri availabilities.');
    }
}
