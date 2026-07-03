<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capture the full payment-plan choice the student/guardian makes on the
     * Financial Assessment step: which option (cash / downpayment / installment),
     * the billing frequency, and their acknowledgement of the late-payment
     * penalty. payment_plan_id already links to the chosen plan; these let the
     * billing pipeline reconstruct the exact schedule that was agreed to.
     */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('payment_option', 20)->nullable()->after('payment_plan_id');
            $table->string('payment_frequency', 20)->nullable()->after('payment_option');
            $table->boolean('agreed_to_penalty')->default(false)->after('payment_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn(['payment_option', 'payment_frequency', 'agreed_to_penalty']);
        });
    }
};
