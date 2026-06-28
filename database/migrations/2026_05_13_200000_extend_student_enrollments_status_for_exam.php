<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'submitted',
                'exam_passed',
                'exam_failed',
                'assessed',
                'billed',
                'partially_paid',
                'enrolled',
                'dropped',
                'cancelled',
                'completed',
            ])->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Can't revert enum shrink safely; leave as is.
    }
};
