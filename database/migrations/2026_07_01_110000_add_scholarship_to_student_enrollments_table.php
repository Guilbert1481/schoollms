<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the diagnostic-exam scholarship on the enrolment so billing bills the
 * true net payable. Previously the scholarship was only computed live for the
 * Financial Assessment preview and never reached the invoice, so the finance
 * manager's issued invoice ignored the discount entirely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('scholarship_label')->nullable()->after('payment_frequency');
            $table->decimal('scholarship_amount', 12, 2)->default(0)->after('scholarship_label');
            $table->decimal('scholarship_percent', 5, 2)->nullable()->after('scholarship_amount');
            $table->string('scholarship_apply_to', 20)->nullable()->after('scholarship_percent');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'scholarship_label',
                'scholarship_amount',
                'scholarship_percent',
                'scholarship_apply_to',
            ]);
        });
    }
};
