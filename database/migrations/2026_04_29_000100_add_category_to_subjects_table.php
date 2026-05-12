<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ALLOWED = ['gen_ed', 'prof_ed', 'major', 'pe', 'nstp', 'internship'];

    public function up(): void
    {
        if (! Schema::hasTable('subjects')) return;

        if (! Schema::hasColumn('subjects', 'category')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->enum('category', self::ALLOWED)
                      ->default('major')
                      ->after('scope')
                      ->index();
            });
        }

        // Backfill — name/code-driven classification
        $rows = DB::table('subjects')->select('id', 'code', 'name')->get();

        foreach ($rows as $r) {
            $code = strtoupper((string) $r->code);
            $name = (string) $r->name;
            $nameUpper = strtoupper($name);

            $category = $this->classify($code, $name, $nameUpper);

            DB::table('subjects')->where('id', $r->id)->update(['category' => $category]);
        }

        // Safety net — anything still null
        DB::table('subjects')->whereNull('category')->update(['category' => 'major']);
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'category')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    private function classify(string $code, string $name, string $nameUpper): string
    {
        // Internship / practice teaching
        if (str_contains($nameUpper, 'INTERNSHIP')
            || str_contains($nameUpper, 'PRACTICE TEACHING')
            || str_starts_with($code, 'FS-')          // Field Study
            || $code === 'PROF-INT') {
            return 'internship';
        }

        // NSTP
        if (str_starts_with($code, 'NSTP') || str_contains($nameUpper, 'NSTP')) {
            return 'nstp';
        }

        // Physical Education
        if (str_starts_with($code, 'PE-')
            || preg_match('/\bPE\b/', $nameUpper)
            || str_contains($nameUpper, 'PHYSICAL EDUCATION')) {
            return 'pe';
        }

        // Professional Education
        if (str_starts_with($code, 'PROF-')) {
            return 'prof_ed';
        }

        // General Education
        if (str_starts_with($code, 'GEC-')
            || str_starts_with($code, 'GE-')
            || str_starts_with($code, 'FIL-')
            || $code === 'RIZAL') {
            return 'gen_ed';
        }

        // Default: major / specialization (MATH-*, ENG-*, LET-REV, etc.)
        return 'major';
    }
};
