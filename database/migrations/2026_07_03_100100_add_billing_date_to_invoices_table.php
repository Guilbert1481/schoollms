<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The date a bill becomes active/collectible — now distinct from due_date,
     * since a payment plan may add a grace window (due_date = billing_date +
     * plan.due_days). The Billing page shows a bill once its billing_date has
     * arrived, so installment invoices dated to a future month stay hidden until
     * then. Null = legacy rows, which the Billing list treats as already billed
     * (always shown) so nothing pre-existing disappears.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'billing_date')) {
                $table->date('billing_date')->nullable()->after('issue_date');
                $table->index(['school_id', 'billing_date'], 'invoices_school_billing_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'billing_date')) {
                $table->dropIndex('invoices_school_billing_date_index');
                $table->dropColumn('billing_date');
            }
        });
    }
};
