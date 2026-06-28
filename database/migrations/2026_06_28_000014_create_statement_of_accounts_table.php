<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A generated Statement of Account: a point-in-time snapshot of a student's
 * ledger for a billing period (the cadence is the finance-configured
 * frequency). `line_items` stores the frozen ledger rows so the document is
 * reproducible even after later ledger activity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_of_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('soa_number', 60)->nullable();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->nullable()
                ->constrained('student_enrollments')->nullOnDelete();

            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();

            $table->string('frequency', 20)->default('per_term'); // monthly|quarterly|per_term|annual|on_demand
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('period_label', 80)->nullable();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('total_charges', 12, 2)->default(0);
            $table->decimal('total_credits', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->default(0);

            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('issued'); // draft|issued|paid|void

            $table->json('line_items')->nullable();

            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'soa_number'], 'soa_school_number_unique');
            $table->index(['school_id', 'student_id', 'period_end'], 'soa_school_student_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_of_accounts');
    }
};
