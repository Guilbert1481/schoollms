<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Registrar — Student Ledgers.
 *
 * Detailed records of officially enrolled students (status = enrolled).
 * Provisionally enrolled, billed, and other in-flight statuses are excluded so
 * the registrar can focus on students whose records are fully compliant.
 */
class StudentLedgerController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        // Top-level education levels offered by the school (tabs / dropdown).
        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $nodeToRoot = $this->buildNodeRootMap();
        $showTabs   = $levels->count() > 1;

        // Lookup of root-level id => name so we can detect Basic Education
        // rows in O(1) when formatting the Year/Term cell.
        $rootNameById = $levels->pluck('name', 'id')->all();

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null
            || $levelParam === ''
            || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;

        // Latest enrollment per student, restricted to officially enrolled.
        $rows = DB::table('students as st')
            ->join('student_enrollments as se', 'se.id', '=', DB::raw(
                '(select max(id) from student_enrollments where student_id = st.id)'
            ))
            ->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
            ->leftJoin('terms as t', 't.id', '=', 'se.term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'se.academic_year_id')
            ->where('st.school_id', $schoolId)
            ->where('se.status', 'enrolled')
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->get([
                'st.id as student_id',
                'st.student_number',
                'st.first_name',
                'st.middle_name',
                'st.last_name',
                'st.email',
                'se.year_level',
                'se.education_node_id',
                'se.academic_year_id',
                'p.code as program_code',
                'p.name as program_name',
                'p.education_node_id as program_node_id',
                't.name as term_name',
                'ay.name as academic_year_name',
                'se.updated_at as enrolled_at',
            ]);

        $academicYearId = $request->query('academic_year_id');
        $academicYearId = ($academicYearId === null || $academicYearId === '') ? null : (int) $academicYearId;

        $yearLevelFilter = $request->query('year_level');
        $yearLevelFilter = ($yearLevelFilter === null || $yearLevelFilter === '') ? null : (string) $yearLevelFilter;

        // Normalise each enrollment into an item carrying both the raw fields
        // used for filtering and the display object rendered by the table.
        $items = $rows->map(function ($r) use ($nodeToRoot, $rootNameById) {
            $rootLevelId = $nodeToRoot[$r->education_node_id ?? null]
                ?? $nodeToRoot[$r->program_node_id ?? null]
                ?? null;

            $fullName = trim(implode(' ', array_filter([
                $r->first_name, $r->middle_name, $r->last_name,
            ]))) ?: '—';

            return (object) [
                'root'               => $rootLevelId,
                'year_level'         => $r->year_level,
                'academic_year_id'   => $r->academic_year_id,
                'academic_year_name' => $r->academic_year_name,
                'display'            => (object) [
                    'full_name'   => $fullName,
                    'student_id'  => $r->student_number ?? '—',
                    'email'       => $r->email ?? '—',
                    'year_term'   => $this->formatYearTerm($r->year_level, $r->term_name, $rootLevelId, $rootNameById),
                    'program'     => $r->program_code
                        ? trim($r->program_code.' — '.$r->program_name)
                        : ($r->program_name ?? '—'),
                    'enrolled_at' => $r->enrolled_at
                        ? \Carbon\Carbon::parse($r->enrolled_at)->format('M d, Y')
                        : '—',
                ],
            ];
        });

        // Tab counts = total enrolled per level (independent of the filters).
        $counts = $items->groupBy(fn ($i) => $i->root ?? 0)->map->count();

        // Restrict to the active level (tab), then layer on the dropdown filters.
        $scoped = $showAll
            ? $items
            : $items->filter(fn ($i) => (int) ($i->root ?? 0) === $activeLevelId)->values();

        // "Basic Education" view: either the active tab is basic, or the school
        // offers only one level and it is basic.
        $activeLevel    = $levels->firstWhere('id', $activeLevelId);
        $singleLevel    = $levels->count() === 1 ? $levels->first() : null;
        $effectiveLevel = $showAll ? $singleLevel : $activeLevel;
        $activeLevelIsBasic = $effectiveLevel
            && str_contains(strtolower((string) $effectiveLevel->name), 'basic');

        // Academic-year filter options (from the active level scope).
        $academicYears = $scoped
            ->filter(fn ($i) => $i->academic_year_id)
            ->unique('academic_year_id')
            ->sortByDesc('academic_year_name')
            ->mapWithKeys(fn ($i) => [(int) $i->academic_year_id => $i->academic_year_name])
            ->all();

        $afterAy = $academicYearId
            ? $scoped->filter(fn ($i) => (int) $i->academic_year_id === $academicYearId)->values()
            : $scoped;

        // Grade / Year-level filter options (after the academic-year filter).
        $yearLevelOptions = $afterAy
            ->filter(fn ($i) => $i->year_level !== null && $i->year_level !== '')
            ->unique('year_level')
            ->sortBy('year_level')
            ->mapWithKeys(function ($i) use ($activeLevelIsBasic) {
                $v = $i->year_level;
                $label = is_numeric($v)
                    ? (($activeLevelIsBasic ? 'Grade ' : 'Year ').(int) $v)
                    : (string) $v;

                return [(string) $v => $label];
            })
            ->all();

        $finalRows = $afterAy
            ->when($yearLevelFilter !== null, fn ($c) => $c->filter(fn ($i) => (string) $i->year_level === $yearLevelFilter))
            ->map(fn ($i) => $i->display)
            ->values();

        // Columns: Basic Education hides the redundant Program column and the
        // Year/Term header reads "Grade Level".
        $columns = config('tables.student_ledgers.columns', []);
        if ($activeLevelIsBasic) {
            $columns = array_values(array_filter($columns, fn ($c) => ($c['key'] ?? null) !== 'program'));
            $columns = array_map(function ($c) {
                if (($c['key'] ?? null) === 'year_term') {
                    $c['label'] = 'Grade Level';
                }

                return $c;
            }, $columns);
        }

        $levelTitle = $showAll
            ? 'All Levels'
            : ($activeLevel?->name ?? ($levels->first()->name ?? null));

        $importTerms = DB::table('terms as t')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 't.academic_year_id')
            ->where('t.school_id', $schoolId)
            ->orderByDesc('t.is_current')
            ->orderByDesc('t.start_date')
            ->orderByDesc('t.id')
            ->get([
                't.id',
                't.name',
                't.academic_year_id',
                't.is_current',
                'ay.name as academic_year_name',
            ]);

        // For the import "Others" path: the academic years the term/AY can be
        // attached to, and whether a higher-ed term number should be requested
        // (true when the school offers any non-Basic-Education level).
        $importAcademicYears = DB::table('academic_years')
            ->where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $showImportTermNumber = $levels
            ->contains(fn ($l) => ! str_contains(strtolower((string) $l->name), 'basic'));

        return view('registrar.student_ledgers.index', [
            'columns'            => $columns,
            'levels'             => $levels,
            'activeLevelId'      => $activeLevelId,
            'showAll'            => $showAll,
            'showTabs'           => $showTabs,
            'levelTitle'         => $levelTitle,
            'rows'               => $finalRows,
            'counts'             => $counts,
            'total'              => $items->count(),
            'importTerms'        => $importTerms,
            'academicYears'      => $academicYears,
            'yearLevelOptions'   => $yearLevelOptions,
            'academicYearId'     => $academicYearId,
            'yearLevel'          => $yearLevelFilter,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'importAcademicYears'  => $importAcademicYears,
            'showImportTermNumber' => $showImportTermNumber,
        ]);
    }

    public function import(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        // "Others" bypasses the term list: the importer specifies an academic
        // year (and, for higher-ed, a term number) instead.
        $isOthers = $request->input('term_id') === 'others';

        $rules = [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
        if ($isOthers) {
            $rules['term_id']       = ['required', 'in:others'];
            $rules['academic_year'] = ['required', 'string', 'max:255'];
            $rules['term_number']   = ['nullable', 'in:1,2,3'];
        } else {
            $rules['term_id'] = ['required', 'integer'];
        }
        $validated = $request->validate($rules);

        if ($isOthers) {
            $term = $this->resolveImportTerm(
                $schoolId,
                (string) $validated['academic_year'],
                $request->input('term_number'),
            );

            if (! $term) {
                return back()->with('error', 'Please enter a valid academic year (for example, 2018 - 2019).');
            }
        } else {
            $term = Term::query()
                ->where('school_id', $schoolId)
                ->findOrFail((int) $validated['term_id']);
        }

        if (! $term->academic_year_id) {
            return back()->with('error', 'The selected term is missing an academic year. Please fix the term setup before importing.');
        }

        [$rows, $parseErrors] = $this->readCsvRows($request->file('file')->getRealPath());

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = $parseErrors;

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                $result = DB::transaction(function () use ($row, $schoolId, $term) {
                    return $this->importStudentRow($row, (int) $schoolId, $term);
                });

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Line {$line}: ".$e->getMessage();
            }
        }

        return back()
            ->with('success', "Import finished. {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', array_slice($errors, 0, 20));
    }

    /**
     * Resolve the Term for the "Others" import path: find-or-create a term
     * under the chosen academic year. A term number produces a higher-ed
     * numbered term; otherwise a Basic-Education enrolment term.
     */
    protected function resolveImportTerm(int $schoolId, string $academicYearName, ?string $termNumber): ?Term
    {
        $name = trim($academicYearName);
        if ($name === '') {
            return null;
        }

        // Find-or-create the academic year so historical years (e.g. 2018-2019)
        // can be added straight from the import — that is the whole point of
        // "Others": loading a school's past records.
        [$startDate, $endDate] = $this->guessAcademicYearDates($name);

        $ay = AcademicYear::firstOrCreate(
            ['school_id' => $schoolId, 'name' => $name],
            ['start_date' => $startDate, 'end_date' => $endDate, 'status' => 'closed']
        );

        if ($termNumber) {
            return Term::firstOrCreate(
                [
                    'school_id'        => $schoolId,
                    'academic_year_id' => $ay->id,
                    'term'             => 'Term '.$termNumber,
                ],
                [
                    'education_level' => 'higher_ed',
                    'enrollment_type' => 'regular',
                    'academic_year'   => mb_substr($name, 0, 20),
                    'name'            => 'Term '.$termNumber.' ('.$name.')',
                    'title'           => 'Term '.$termNumber,
                    'start_date'      => $ay->start_date ?? $startDate,
                    'end_date'        => $ay->end_date ?? $endDate,
                    'status'          => 'active',
                    'is_active'       => 1,
                ]
            );
        }

        return Term::firstOrCreate(
            [
                'school_id'        => $schoolId,
                'academic_year_id' => $ay->id,
                'term'             => 'Enrollment',
            ],
            [
                'education_level' => 'basic_ed',
                'enrollment_type' => 'regular',
                'academic_year'   => mb_substr($name, 0, 20),
                'name'            => 'Basic Ed ('.$name.')',
                'title'           => 'Basic Ed',
                'start_date'      => $ay->start_date ?? $startDate,
                'end_date'        => $ay->end_date ?? $endDate,
                'status'          => 'active',
                'is_active'       => 1,
            ]
        );
    }

    /**
     * Best-effort start/end dates from an academic-year label like "2018-2019".
     * Falls back to today..+1 year when no years can be parsed.
     *
     * @return array{0:string,1:string}
     */
    protected function guessAcademicYearDates(string $name): array
    {
        if (preg_match_all('/\d{4}/', $name, $m) && ! empty($m[0])) {
            $y1 = (int) $m[0][0];
            $y2 = isset($m[0][1]) ? (int) $m[0][1] : $y1 + 1;

            return [sprintf('%04d-06-01', $y1), sprintf('%04d-04-30', $y2)];
        }

        return [now()->toDateString(), now()->addYear()->toDateString()];
    }

    /**
     * Format the Year/Term cell. Basic Education rows render with a
     * "Grade {N}" prefix, every other level uses "Year {N}". The term name
     * (when available) is appended after a dash. Examples:
     *
     *   Basic Education : "Grade 4 - Semester 2"
     *   Other levels    : "Year 1 - 2nd Semester"
     */
    protected function formatYearTerm($yearLevel, ?string $termName, ?int $rootLevelId, array $rootNameById): string
    {
        $isBasicEd = $rootLevelId
            && str_contains(strtolower((string) ($rootNameById[$rootLevelId] ?? '')), 'basic');

        $yearPart = null;
        if ($yearLevel !== null && $yearLevel !== '' && $yearLevel !== '—') {
            $yearPart = is_numeric($yearLevel)
                ? ($isBasicEd ? 'Grade ' : 'Year ').(int) $yearLevel
                : (string) $yearLevel;
        }

        $termPart = trim((string) ($termName ?? ''));

        if ($yearPart && $termPart !== '') {
            return $yearPart.' - '.$termPart;
        }
        if ($yearPart) {
            return $yearPart;
        }
        if ($termPart !== '') {
            return $termPart;
        }
        return '—';
    }

    protected function buildNodeRootMap(): array
    {
        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }
        return $rootOf;
    }

    protected function importStudentRow(array $row, int $schoolId, Term $defaultTerm): string
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName  = trim((string) ($row['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            throw new \RuntimeException('First name and last name are required.');
        }

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;

        $studentNumber = trim((string) ($row['student_number'] ?? ''));
        if ($studentNumber !== '') {
            $owner = Student::query()
                ->where('student_number', $studentNumber)
                ->first();

            if ($owner && (int) $owner->school_id !== $schoolId) {
                throw new \RuntimeException("Student number {$studentNumber} belongs to another school.");
            }
        }

        $user = $email ? User::query()->where('email', $email)->first() : null;
        if ($user && (int) $user->school_id !== $schoolId) {
            throw new \RuntimeException("Email {$email} belongs to another school.");
        }
        if ($user && $user->role !== 'student') {
            throw new \RuntimeException("Email {$email} belongs to a non-student account.");
        }

        if ($studentNumber !== '' && $user) {
            $studentForUser = Student::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->first();

            if ($studentForUser && $studentForUser->student_number !== $studentNumber) {
                throw new \RuntimeException("Email {$email} is already linked to student {$studentForUser->student_number}.");
            }
        }

        if (! $user && $email) {
            $user = User::create([
                'first_name'  => $firstName,
                'middle_name' => $this->clean($row['middle_name'] ?? null),
                'last_name'   => $lastName,
                'email'       => $email,
                'password'    => Hash::make(Str::random(16)),
                'role'        => 'student',
                'school_id'   => $schoolId,
                'phone'       => $this->clean($row['phone'] ?? $row['mobile_number'] ?? null),
            ]);
        } elseif ($user) {
            $user->fill([
                'first_name'  => $firstName,
                'middle_name' => $this->clean($row['middle_name'] ?? null),
                'last_name'   => $lastName,
                'phone'       => $this->clean($row['phone'] ?? $row['mobile_number'] ?? $user->phone),
            ])->save();
        }

        $student = null;
        if ($studentNumber !== '') {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('student_number', $studentNumber)
                ->first();
        }
        if (! $student && $email) {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('email', $email)
                ->first();
        }
        if (! $student && $user) {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->first();
        }

        $wasExistingStudent = (bool) $student;
        $studentNumber = $studentNumber !== ''
            ? $studentNumber
            : ($student?->student_number ?: $this->generateStudentNumber());

        $studentData = [
            'school_id'            => $schoolId,
            'user_id'              => $user?->id,
            'student_number'       => $studentNumber,
            'first_name'           => $firstName,
            'middle_name'          => $this->clean($row['middle_name'] ?? null),
            'last_name'            => $lastName,
            'email'                => $email,
            'phone'                => $this->clean($row['phone'] ?? null),
            'mobile_number'        => $this->clean($row['mobile_number'] ?? $row['phone'] ?? null),
            'gender'               => $this->clean($row['gender'] ?? null),
            'date_of_birth'        => $this->parseDate($row['date_of_birth'] ?? $row['birthdate'] ?? null),
            'nationality'          => $this->clean($row['nationality'] ?? null),
            'barangay'             => $this->clean($row['barangay'] ?? null),
            'city_municipality'    => $this->clean($row['city_municipality'] ?? $row['city'] ?? null),
            'province'             => $this->clean($row['province'] ?? null),
            'region'               => $this->clean($row['region'] ?? null),
            'zip_code'             => $this->clean($row['zip_code'] ?? null),
            'address_line_1'       => $this->clean($row['address_line_1'] ?? $row['address'] ?? null),
            'address_line_2'       => $this->clean($row['address_line_2'] ?? null),
        ];

        $studentData = $this->filterColumns('students', $studentData);

        if ($student) {
            $student->fill($studentData)->save();
        } else {
            $student = Student::create($studentData);
        }

        if ($user) {
            $this->syncProfileAndAccess($user, $student, $schoolId);
        }

        $term = $defaultTerm;
        $rowTermId = (int) ($row['term_id'] ?? 0);
        if ($rowTermId > 0 && $rowTermId !== (int) $defaultTerm->id) {
            $term = Term::query()
                ->where('school_id', $schoolId)
                ->findOrFail($rowTermId);
        }

        if (! $term->academic_year_id) {
            throw new \RuntimeException("Term {$term->id} is missing an academic year.");
        }

        $program = $this->resolveProgram($row, $schoolId);
        $enrollment = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->where('term_id', $term->id)
            ->first();

        $enrollmentData = [
            'school_id'          => $schoolId,
            'student_id'         => $student->id,
            'academic_year_id'   => $term->academic_year_id,
            'term_id'            => $term->id,
            'program_id'         => $program?->id,
            'education_node_id'  => (int) ($row['education_node_id'] ?? 0) ?: $program?->education_node_id,
            'year_level'         => (int) ($row['year_level'] ?? 0) ?: null,
            'student_type'       => $this->clean($row['student_type'] ?? null) ?: 'continuing',
            'enrollee_type'      => $this->clean($row['enrollee_type'] ?? null) ?: 'continuing',
            'program_type'       => $this->clean($row['program_type'] ?? null) ?: 'regular',
            'education_level'    => $this->clean($row['education_level'] ?? null),
            'status'             => StudentEnrollment::STATUS_ENROLLED,
            'approval_level'     => 'import',
            'approved_by'        => auth()->id(),
            'approved_at'        => now(),
            'remarks'            => 'Imported as enrolled student profile.',
        ];

        $enrollmentData = $this->filterColumns('student_enrollments', $enrollmentData);

        if ($enrollment) {
            $enrollment->fill($enrollmentData)->save();
        } else {
            StudentEnrollment::create($enrollmentData);
        }

        return $wasExistingStudent || $enrollment ? 'updated' : 'created';
    }

    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [[], ['The CSV file is empty.']];
        }

        $keys = array_map(fn ($h) => $this->normalizeHeader((string) $h), $header);
        $rows = [];
        $errors = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count(array_filter($line, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($keys as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $line[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [$rows, $errors];
    }

    protected function normalizeHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim((string) $key, '_');

        return match ($key) {
            'student_no', 'student_id', 'id_number', 'id_no' => 'student_number',
            'given_name', 'firstname' => 'first_name',
            'middlename' => 'middle_name',
            'surname', 'lastname', 'family_name' => 'last_name',
            'birth_date', 'birthday', 'dob' => 'date_of_birth',
            'program', 'course', 'course_code' => 'program_code',
            'grade_level', 'year', 'grade' => 'year_level',
            'contact_number', 'mobile', 'mobile_no' => 'mobile_number',
            default => $key,
        };
    }

    protected function resolveProgram(array $row, int $schoolId): ?object
    {
        $code = trim((string) ($row['program_code'] ?? ''));
        $name = trim((string) ($row['program_name'] ?? ''));

        if ($code === '' && $name === '') {
            return null;
        }

        return DB::table('programs')
            ->where('school_id', $schoolId)
            ->when($code !== '', fn ($q) => $q->whereRaw('LOWER(code) = ?', [strtolower($code)]))
            ->when($code === '' && $name !== '', fn ($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($name)]))
            ->first();
    }

    protected function syncProfileAndAccess(User $user, Student $student, int $schoolId): void
    {
        $profile = DB::table('profiles')->where('user_id', $user->id)->first();
        $profileData = $this->filterColumns('profiles', [
            'user_id'      => $user->id,
            'school_id'    => $schoolId,
            'profile_type' => 'student',
            'profile_code' => $student->student_number,
            'first_name'   => $student->first_name,
            'middle_name'  => $student->middle_name,
            'last_name'    => $student->last_name,
            'gender'       => $student->gender,
            'birthday'     => $student->date_of_birth,
            'contact_number' => $student->mobile_number ?: $student->phone,
            'address'      => $student->address_line_1,
            'city'         => $student->city_municipality,
            'province'     => $student->province,
            'country'      => $student->country,
            'nationality'  => $student->nationality,
            'status'       => 'active',
            'updated_at'   => now(),
        ]);

        if ($profile) {
            DB::table('profiles')->where('id', $profile->id)->update($profileData);
            $profileId = $profile->id;
        } else {
            $profileData['created_at'] = now();
            $profileId = DB::table('profiles')->insertGetId($profileData);
        }

        $roleId = $this->ensureStudentRole($schoolId);
        $accessData = $this->filterColumns('account_access', [
            'user_id'       => $user->id,
            'role_id'       => $roleId,
            'person_id'     => $profileId,
            'office_id'     => null,
            'role_snapshot' => 'student',
            'start_date'    => now()->toDateString(),
            'assigned_by'   => auth()->id(),
            'remarks'       => 'Imported enrolled student profile',
            'is_active'     => 1,
            'updated_at'    => now(),
        ]);

        $existingAccess = DB::table('account_access')
            ->where('user_id', $user->id)
            ->when(Schema::hasColumn('account_access', 'role_id'), fn ($q) => $q->where('role_id', $roleId))
            ->first();

        if ($existingAccess) {
            DB::table('account_access')->where('id', $existingAccess->id)->update($accessData);
        } else {
            $accessData['created_at'] = now();
            DB::table('account_access')->insert($accessData);
        }
    }

    protected function ensureStudentRole(int $schoolId): int
    {
        $role = DB::table('roles')
            ->where('school_id', $schoolId)
            ->where('name', 'student')
            ->first();

        if ($role) {
            return (int) $role->id;
        }

        $data = [
            'school_id'  => $schoolId,
            'name'       => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('roles', 'authority_level')) {
            $data['authority_level'] = 0;
        }

        return (int) DB::table('roles')->insertGetId($data);
    }

    protected function generateStudentNumber(): string
    {
        $year = now()->format('Y');

        do {
            $candidate = sprintf('S-%s-%06d', $year, random_int(1, 999999));
        } while (Student::where('student_number', $candidate)->exists());

        return $candidate;
    }

    protected function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }

    protected function clean($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function parseDate($value): ?string
    {
        $value = $this->clean($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new \RuntimeException("Invalid date value '{$value}'.");
        }
    }
}
