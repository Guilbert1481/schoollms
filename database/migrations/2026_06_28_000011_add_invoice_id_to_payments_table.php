<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links each payment to the invoice it settles (the FK PaymentGateValidator
 * already sums on). Nullable so ad-hoc / general payments that are not tied to
 * a specific invoice still record cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('invoices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'invoice_id')) {
                $table->dropConstrainedForeignId('invoice_id');
            }
        });
    }
};
