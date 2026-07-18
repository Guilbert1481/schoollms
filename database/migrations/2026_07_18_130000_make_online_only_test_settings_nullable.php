<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Timer/availability, attempts, passing score and result visibility are all
     * ONLINE-delivery settings. An F2F test is printed and scanned, so the builder
     * hides that whole block and posts none of it — these columns must accept NULL.
     *
     * `availability_mode` was the hard blocker: NOT NULL defaulting to 'duration',
     * which forced every test (printed ones included) to claim either a timer or a
     * schedule before it could be saved.
     *
     * timer_minutes / start_at / end_at / duration_minutes are already nullable, and
     * the shuffle flags stay NOT NULL (booleans with a sensible 0 default).
     */
    public function up(): void
    {
        Schema::table('test_settings', function (Blueprint $table) {
            $table->string('availability_mode')->nullable()->default(null)->change();
            $table->integer('attempts_allowed')->nullable()->default(null)->change();
            $table->integer('passing_score')->nullable()->default(null)->change();
            $table->string('show_results')->nullable()->default(null)->change();
            $table->string('show_correct_answers')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // F2F rows written since `up()` hold NULLs, which a NOT NULL column would
        // reject — backfill them to the previous defaults first.
        DB::table('test_settings')->whereNull('availability_mode')->update(['availability_mode' => 'duration']);
        DB::table('test_settings')->whereNull('attempts_allowed')->update(['attempts_allowed' => 1]);
        DB::table('test_settings')->whereNull('passing_score')->update(['passing_score' => 75]);
        DB::table('test_settings')->whereNull('show_results')->update(['show_results' => 'after_exam']);
        DB::table('test_settings')->whereNull('show_correct_answers')->update(['show_correct_answers' => 'after_exam']);

        Schema::table('test_settings', function (Blueprint $table) {
            $table->string('availability_mode')->nullable(false)->default('duration')->change();
            $table->integer('attempts_allowed')->nullable(false)->default(1)->change();
            $table->integer('passing_score')->nullable(false)->default(75)->change();
            $table->string('show_results')->nullable(false)->default('after_exam')->change();
            $table->string('show_correct_answers')->nullable(false)->default('after_exam')->change();
        });
    }
};
