<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_credit_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // The curriculum subject the student wants credit for.
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            // Optional: the previous-school / source subject being credited.
            $table->string('source_subject_code')->nullable();
            $table->string('source_subject_title')->nullable();
            $table->string('source_school')->nullable();
            $table->string('source_grade')->nullable();
            $table->decimal('source_units', 4, 1)->nullable();

            $table->decimal('credited_units', 4, 1)->nullable();

            // pending | credited | rejected | conditional
            $table->string('status', 20)->default('pending');

            // transferee | irregular | shifter | returnee | other
            $table->string('reason', 30)->default('transferee');

            $table->text('remarks')->nullable();

            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_credit_evaluations');
    }
};
