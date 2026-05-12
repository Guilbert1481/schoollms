<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-applies the explicit BSEd-English tri-sem layout to program_subjects.
 *
 *  php artisan program-subjects:apply-bsed-eng [--program=2] [--dry-run]
 *
 * - Wipes program_subjects rows for the target program.
 * - Re-inserts them according to the curated mapping below
 *   (year_level + semester_number 1|2|3).
 * - Creates any missing subjects (e.g. LET-REV) on the fly.
 * - Flips the curriculum to 3 terms/year, no summer.
 */
class ApplyBsedEnglishLayout extends Command
{
    protected $signature = 'program-subjects:apply-bsed-eng
                            {--program= : Program ID (defaults to BSED-ENG)}
                            {--dry-run : Preview only}';

    protected $description = 'Apply the curated BSEd-English 3-term layout to program_subjects.';

    /** [code, year, term, name (used only if subject must be created)] */
    private array $layout = [
        // YEAR 1
        ['GEC-USELF', 1, 1, 'Understanding the Self'],
        ['GEC-PURCO', 1, 1, 'Purposive Communication'],
        ['FIL-1',     1, 1, 'Filipino sa Iba\'t Ibang Disiplina'],
        ['PROF-CALL', 1, 1, 'The Child and Adolescent Learners'],
        ['PE-1',      1, 1, 'Physical Education 1 (Movement Enhancement)'],

        ['GEC-MMW',   1, 2, 'Mathematics in the Modern World'],
        ['GEC-RPH',   1, 2, 'Readings in Philippine History'],
        ['PROF-TP',   1, 2, 'The Teaching Profession'],
        ['NSTP-1',    1, 2, 'NSTP 1'],
        ['PE-2',      1, 2, 'Physical Education 2 (Fitness Exercises)'],

        ['GEC-TCW',   1, 3, 'The Contemporary World'],
        ['FIL-2',     1, 3, 'Pagbasa at Pagsulat sa Iba\'t Ibang Disiplina'],
        ['ENG-LING',  1, 3, 'Introduction to Linguistics'],
        ['NSTP-2',    1, 3, 'NSTP 2'],
        ['PE-3',      1, 3, 'Physical Education 3 (Individual/Dual Sports)'],
        ['GE-ENVSC',  1, 3, 'Environmental Science'],

        // YEAR 2
        ['GEC-ART',   2, 1, 'Art Appreciation'],
        ['PROF-FSIE', 2, 1, 'Foundation of Special and Inclusive Education'],
        ['ENG-STRC',  2, 1, 'Structure of English'],
        ['ENG-SEAL',  2, 1, 'Survey of English and American Literature'],
        ['PE-4',      2, 1, 'Physical Education 4 (Team Sports)'],

        ['GEC-ETH',   2, 2, 'Ethics'],
        ['PROF-FLCT', 2, 2, 'Facilitating Learner-Centered Teaching'],
        ['ENG-LCS',   2, 2, 'Language, Culture and Society'],
        ['ENG-MYTH',  2, 2, 'Mythology and Folklore'],
        ['GEC-STS',   2, 2, 'Science, Technology and Society'],

        ['RIZAL',     2, 3, 'Life and Works of Rizal'],
        ['PROF-TCSC', 2, 3, 'The Teacher and the Community, School Culture'],
        ['PROF-TTL1', 2, 3, 'Technology for Teaching and Learning 1'],
        ['PROF-TSC',  2, 3, 'The Teacher and the School Curriculum'],
        ['ENG-MACRO', 2, 3, 'Teaching and Assessment of Macroskills'],
        ['ENG-TWET',  2, 3, 'Technical Writing for English Teachers'],

        // YEAR 3
        ['ENG-PTLA',  3, 1, 'Principles & Theories of Language Acquisition'],
        ['ENG-SPLE',  3, 1, 'Survey of Philippine Literature in English'],
        ['ENG-SPCH',  3, 1, 'Speech and Theater Arts'],
        ['PROF-AL1',  3, 1, 'Assessment in Learning 1'],
        ['GE-LITERA', 3, 1, 'Living in the IT Era'],

        ['ENG-AFRO',  3, 2, 'Survey of Afro-Asian Literature'],
        ['ENG-CHILD', 3, 2, 'Children and Adolescent Literature'],
        ['PROF-NLAC', 3, 2, 'Building & Enhancing New Literacies'],
        ['GE-GENDR',  3, 2, 'Gender and Society'],
        ['PROF-RES1', 3, 2, 'Research 1: Methods of Research'],

        ['PROF-AL2',  3, 3, 'Assessment in Learning 2'],
        ['ENG-GRAM',  3, 3, 'Teaching and Assessment of the Grammar'],
        ['ENG-TTL2',  3, 3, 'Technology for Teaching & Learning 2 (English)'],
        ['PROF-RES2', 3, 3, 'Research 2: Action Research'],

        // YEAR 4
        ['FS-1',      4, 1, 'Field Study 1: Observation of Teaching'],
        ['ENG-LIT',   4, 1, 'Teaching and Assessment of Literature Studies'],
        ['ENG-CONTP', 4, 1, 'Contemporary, Popular and Emergent Literature'],

        ['FS-2',      4, 2, 'Field Study 2: Participation & Teaching Assistance'],
        ['ENG-STYL',  4, 2, 'Stylistics and Discourse Analysis'],
        ['ENG-CAMPJ', 4, 2, 'Campus Journalism'],
        ['ENG-LLMD',  4, 2, 'Language Learning Materials Development'],

        ['PROF-INT',  4, 3, 'Teaching Internship'],
        ['ENG-LER',   4, 3, 'Language Education Research'],
        ['LET-REV',   4, 3, 'LET Review'],
    ];

    public function handle(): int
    {
        $programId = (int) ($this->option('program') ?: $this->resolveDefaultProgramId());
        if (! $programId) {
            $this->error('Could not resolve program. Pass --program=ID.');
            return self::FAILURE;
        }

        $program = DB::table('programs')->where('id', $programId)->first();
        if (! $program) {
            $this->error("Program {$programId} not found.");
            return self::FAILURE;
        }

        $this->info("Target: {$program->code} - {$program->name} (program_id={$programId})");

        $schoolId = (int) $program->school_id;
        $dry      = (bool) $this->option('dry-run');

        // Resolve / create subjects ------------------------------------
        $codeToSubject = [];
        $created = 0;
        foreach ($this->layout as [$code, $year, $term, $name]) {
            $row = DB::table('subjects')
                ->where('school_id', $schoolId)
                ->where('code', $code)
                ->first();

            if ($row) {
                $codeToSubject[$code] = (int) $row->id;
                continue;
            }

            if ($dry) {
                $this->line("  [dry] would create subject {$code} - {$name}");
                $codeToSubject[$code] = -1;
            } else {
                $codeToSubject[$code] = (int) DB::table('subjects')->insertGetId([
                    'school_id'  => $schoolId,
                    'code'       => $code,
                    'name'       => $name,
                    'is_active'  => 1,
                    'scope'      => Schema::hasColumn('subjects', 'scope') ? 'academic' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
                $this->line("  + created subject {$code}");
            }
        }
        if ($created > 0) $this->info("Created {$created} new subject(s).");

        // Wipe + re-insert program_subjects -----------------------------
        $existing = DB::table('program_subjects')->where('program_id', $programId)->count();
        $this->line("Existing program_subjects rows: {$existing}");

        if (! $dry) {
            DB::table('program_subjects')->where('program_id', $programId)->delete();
        }

        $now    = now();
        $insert = [];
        foreach ($this->layout as [$code, $year, $term, $_]) {
            $sid = $codeToSubject[$code] ?? null;
            if (! $sid || $sid < 0) continue;
            $insert[] = [
                'program_id'      => $programId,
                'subject_id'      => $sid,
                'year_level'      => $year,
                'semester_number' => $term,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        if ($dry) {
            $this->info('[dry] would insert '.count($insert).' program_subjects rows.');
        } else {
            foreach (array_chunk($insert, 100) as $chunk) {
                DB::table('program_subjects')->insert($chunk);
            }
            $this->info('Inserted '.count($insert).' program_subjects rows.');
        }

        // Curriculum: trisem, no summer ---------------------------------
        if (! $dry && Schema::hasTable('curriculums')) {
            $update = ['updated_at' => $now];
            if (Schema::hasColumn('curriculums', 'terms_per_year')) $update['terms_per_year']  = 3;
            if (Schema::hasColumn('curriculums', 'has_summer_term')) $update['has_summer_term'] = 0;
            DB::table('curriculums')->where('program_id', $programId)->update($update);
            $this->line('Updated curriculum: terms_per_year=3, has_summer_term=0.');
        }

        // Print summary -------------------------------------------------
        $this->newLine();
        $this->info('Final layout:');
        foreach ([1,2,3,4] as $y) {
            foreach ([1,2,3] as $t) {
                $codes = collect($this->layout)
                    ->filter(fn($r) => $r[1] === $y && $r[2] === $t)
                    ->map(fn($r) => $r[0])->values()->all();
                if (! $codes) continue;
                $this->line(sprintf("  Y%d-T%d (%d): %s", $y, $t, count($codes), implode(', ', $codes)));
            }
        }

        return self::SUCCESS;
    }

    private function resolveDefaultProgramId(): ?int
    {
        return DB::table('programs')->where('code', 'BSED-ENG')->value('id');
    }
}
