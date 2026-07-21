<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student Services — three registrar-managed request workflows in one set:
 *
 *   - modality_requests: a non-basic-ed student asks to switch learning
 *     modality (f2f/online/modular). Open only for 2 weeks after the official
 *     enrollment date (approved_at, else created_at); the registrar approves
 *     (which updates student_enrollments.modality_id) or denies.
 *
 *   - document_requests: a student requests a document from the global
 *     `documents` catalog. Lifecycle: pending → processing → ready → released
 *     (or denied at any pre-released step), handled by the registrar.
 *
 *   - clearances (+ clearance_items, clearance_signatories): a student starts
 *     a clearance; one row per signatory is generated. Signatories are
 *     per-school and registrar-configurable (clearance_signatories) with
 *     seeded defaults: Finance / Cashier, Registrar, Guidance, Librarian and
 *     Subject Teachers. A `subject_teachers`-type signatory expands into one
 *     item per (subject, teacher) of the student's current enrollment. Labels
 *     are snapshotted on the item so deleting a signatory later never
 *     invalidates existing clearances (signatory_id just nulls out).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modality_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();

            $table->foreignId('from_modality_id')->nullable()->constrained('modalities')->nullOnDelete();
            $table->foreignId('to_modality_id')->constrained('modalities')->cascadeOnDelete();

            $table->string('reason')->nullable();

            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->string('decision_remarks')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['student_id']);
        });

        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

            $table->string('purpose');
            $table->unsignedSmallInteger('copies')->default(1);

            $table->enum('status', ['pending', 'processing', 'ready', 'released', 'denied'])->default('pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->dateTime('released_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['student_id']);
        });

        Schema::create('clearance_signatories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('name');
            // department = one clearance row; subject_teachers = expands into
            // one row per (subject, teacher) of the student's enrollment.
            $table->enum('type', ['department', 'subject_teachers'])->default('department');
            $table->enum('applies_to', ['basic', 'higher', 'both'])->default('both');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['school_id', 'sort_order']);
        });

        Schema::create('clearances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();

            $table->string('purpose', 100);
            $table->string('note')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['student_id']);
        });

        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('clearance_id')->constrained('clearances')->cascadeOnDelete();

            // Frozen display label; the FK is informational and may null out
            // if the registrar later deletes/renames the signatory.
            $table->string('label');
            $table->foreignId('clearance_signatory_id')->nullable()->constrained('clearance_signatories')->nullOnDelete();

            // Set only on subject-teacher rows.
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['pending', 'cleared', 'hold'])->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acted_at')->nullable();
            $table->string('remarks')->nullable();

            $table->timestamps();

            $table->index(['clearance_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_items');
        Schema::dropIfExists('clearances');
        Schema::dropIfExists('clearance_signatories');
        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('modality_requests');
    }
};
