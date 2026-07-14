<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an invoice back to the incidental_fee that spawned it. Doubles as the
 * duplicate guard: the fan-out skips any (incidental_fee_id, student_enrollment_id)
 * pair that already has an invoice, so re-saving a fee never double-charges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('incidental_fee_id')->nullable()->after('student_enrollment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('incidental_fee_id');
        });
    }
};
