<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the dormant `invoices` table into a real, enrollment-linked invoice
 * header. Adds the foreign keys the PaymentGateValidator already expects
 * (invoices.student_enrollment_id) plus the metadata an itemised invoice and
 * Statement of Account need (number, dates, subtotal, discount).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number', 60)->nullable()->after('id');
            }
            if (! Schema::hasColumn('invoices', 'student_enrollment_id')) {
                $table->foreignId('student_enrollment_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('student_enrollments')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('student_enrollment_id');
            }
            if (! Schema::hasColumn('invoices', 'term_id')) {
                $table->unsignedBigInteger('term_id')->nullable()->after('academic_year_id');
            }
            if (! Schema::hasColumn('invoices', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('term_id');
            }
            if (! Schema::hasColumn('invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
            }
            if (! Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'issued_by')) {
                $table->unsignedBigInteger('issued_by')->nullable()->after('issue_date');
            }
            if (! Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('issued_by');
            }

            $table->unique(['school_id', 'invoice_number'], 'invoices_school_number_unique');
            $table->index(['school_id', 'student_id'], 'invoices_school_student_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_school_number_unique');
            $table->dropIndex('invoices_school_student_index');

            if (Schema::hasColumn('invoices', 'student_enrollment_id')) {
                $table->dropConstrainedForeignId('student_enrollment_id');
            }

            foreach ([
                'invoice_number', 'academic_year_id', 'term_id', 'subtotal_amount',
                'discount_amount', 'issue_date', 'issued_by', 'notes',
            ] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
