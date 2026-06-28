<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Basic Education enrollments don't have a program_id (programmes
        // only exist for SHS strands and higher-ed), so make the column
        // nullable.
        DB::statement('ALTER TABLE `student_enrollments` MODIFY `program_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `student_enrollments` MODIFY `program_id` BIGINT UNSIGNED NOT NULL');
    }
};
