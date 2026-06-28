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
                'provisional',
                'rejected',
                'sent_billing',
                'billed',
                'partially_paid',
                'enrolled',
                'provisionally_enrolled',
                'dropped',
                'cancelled',
                'completed',
            ])->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Non-destructive: leave enum as is.
    }
};
