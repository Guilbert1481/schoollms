<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add nullable school_id first so we can backfill existing rows.
        Schema::table('academic_levels', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable()
                ->after('id')
                ->constrained('schools')
                ->cascadeOnDelete();
        });

        // 2) Drop the old global unique(name, type) constraint, if present.
        try {
            Schema::table('academic_levels', function (Blueprint $table) {
                $table->dropUnique(['name', 'type']);
            });
        } catch (\Throwable $e) {
            // ignore if it was never created
        }

        // 3) Backfill: replicate existing global rows for every school
        //    so each school owns its own copy.
        $schoolIds = DB::table('schools')->pluck('id');
        if ($schoolIds->isNotEmpty()) {
            $globalRows = DB::table('academic_levels')
                ->whereNull('school_id')
                ->get();

            if ($globalRows->isNotEmpty()) {
                $firstSchoolId = $schoolIds->first();

                // Assign existing rows to the first school.
                DB::table('academic_levels')
                    ->whereNull('school_id')
                    ->update(['school_id' => $firstSchoolId]);

                // Replicate for the remaining schools.
                foreach ($schoolIds->slice(1) as $sid) {
                    foreach ($globalRows as $row) {
                        DB::table('academic_levels')->insert([
                            'school_id'      => $sid,
                            'name'           => $row->name,
                            'sequence_order' => $row->sequence_order,
                            'type'           => $row->type,
                            'created_at'     => $row->created_at,
                            'updated_at'     => $row->updated_at,
                        ]);
                    }
                }
            }
        }

        // 4) Make school_id required and add the per-school unique index.
        Schema::table('academic_levels', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->unique(['school_id', 'name', 'type'], 'academic_levels_school_name_type_unique');
        });

        // 5) Repoint dependent rows so they reference the level owned by their own school.
        //    Currently only `questions.academic_level_id` carries this FK.
        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'academic_level_id')) {
            $levels = DB::table('academic_levels')->get(['id', 'school_id', 'name', 'type'])
                ->groupBy(fn ($r) => $r->school_id . '|' . strtolower(trim($r->name)) . '|' . strtolower(trim($r->type)));

            $rows = DB::table('questions as q')
                ->join('subjects as s', 's.id', '=', 'q.subject_id')
                ->join('academic_levels as al', 'al.id', '=', 'q.academic_level_id')
                ->select('q.id as qid', 's.school_id as target', 'al.name', 'al.type', 'al.school_id as current')
                ->get();

            foreach ($rows as $r) {
                if ($r->current == $r->target) continue;
                $key = $r->target . '|' . strtolower(trim($r->name)) . '|' . strtolower(trim($r->type));
                $m = $levels->get($key);
                if ($m && $m->isNotEmpty()) {
                    DB::table('questions')->where('id', $r->qid)->update(['academic_level_id' => $m->first()->id]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('academic_levels', function (Blueprint $table) {
            $table->dropUnique('academic_levels_school_name_type_unique');
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
            $table->unique(['name', 'type']);
        });
    }
};
