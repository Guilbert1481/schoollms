<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Problems the parent-portal auto-provisioner could not resolve on its own,
 * surfaced on the registrar's Parent Portal panel instead of failing silently:
 * - no_email:         the student has no guardian email on file
 * - email_collision:  the guardian email already belongs to a non-parent user
 *                     (never merged automatically — registrar decides)
 * - send_failed:      the account exists but the credential email did not send
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_provisioning_issues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->nullable()
                ->constrained('student_enrollments')->nullOnDelete();

            $table->string('issue', 32); // no_email|email_collision|send_failed
            $table->string('email')->nullable();
            $table->string('details', 500)->nullable();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'resolved_at'], 'ppi_school_resolved_index');
            $table->index(['student_id', 'issue'], 'ppi_student_issue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_provisioning_issues');
    }
};
