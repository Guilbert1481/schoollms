<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_conference_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_name');
            $table->text('notes')->nullable();
            $table->boolean('allow_repeated_room_creation')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'is_active'], 'vcp_school_student_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_conference_permissions');
    }
};
