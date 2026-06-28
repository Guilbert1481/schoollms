<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `student_enrollment_subjects`
            MODIFY COLUMN `status` ENUM(
                'enrolled',
                'dropped',
                'completed',
                'passed',
                'failed',
                'credit'
            ) NOT NULL DEFAULT 'enrolled'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `student_enrollment_subjects`
            SET `status` = 'completed'
            WHERE `status` IN ('passed','failed','credit')");

        DB::statement("ALTER TABLE `student_enrollment_subjects`
            MODIFY COLUMN `status` ENUM(
                'enrolled',
                'dropped',
                'completed'
            ) NOT NULL DEFAULT 'enrolled'");
    }
};
