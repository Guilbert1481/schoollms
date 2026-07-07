<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent portal access grants: which parent-portal user (users.role = 'parent')
 * may see which student. This is deliberately SEPARATE from the `guardians`
 * table — guardians are enrollment contact data that the wizard deletes and
 * recreates on every family-form save, so an access grant hung there would be
 * wiped. One parent user can be linked to many students (multiple children,
 * across schools) and one student can be linked to many parent users
 * (co-parents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->string('relationship', 64)->nullable(); // mother|father|guardian|... (from the guardian row)
            $table->boolean('is_primary')->default(false);  // mirrors guardians.is_primary at provision time
            $table->string('access_status', 32)->default('active'); // active|disabled (registrar kill-switch)

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['parent_user_id', 'student_id'], 'parent_student_link_unique');
            $table->index('student_id', 'parent_student_student_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
    }
};
