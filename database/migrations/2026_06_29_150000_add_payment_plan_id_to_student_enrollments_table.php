<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payment plan a student/guardian selects for an enrolment. This choice
     * gates billing: no plan selected → no invoice, ledger, or SOA. Invoices are
     * generated from the enrolment, so they inherit the plan through this link
     * (and the Invoices list can be filtered by it).
     */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('payment_plan_id')
                ->nullable()
                ->after('program_id')
                ->constrained('payment_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign(['payment_plan_id']);
            $table->dropColumn('payment_plan_id');
        });
    }
};
