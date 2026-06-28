<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The individual student ledger. One append-only row per financial event
 * (charge / payment / discount / adjustment / refund) carrying a running
 * `balance_after` so a Statement of Account can be reconstructed for any
 * period without recomputing the whole history.
 *
 * Convention: a positive balance means the student OWES the school.
 *   - charges increase the balance via `debit`
 *   - payments / credits decrease it via `credit`
 *   - balance_after = previous balance + debit - credit
 *
 * Keyed on student_id => users.id to stay consistent with the existing
 * invoices and payments tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('student_enrollment_id')->nullable()
                ->constrained('student_enrollments')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()
                ->constrained('invoices')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()
                ->constrained('payments')->nullOnDelete();

            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();

            $table->date('entry_date');
            $table->string('type', 20)->default('charge'); // charge|payment|discount|adjustment|refund
            $table->string('description', 191);
            $table->string('reference', 60)->nullable();

            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'entry_date'], 'ledger_school_student_date_index');
            $table->index(['school_id', 'student_id', 'id'], 'ledger_school_student_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
