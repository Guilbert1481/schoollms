<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_conference_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('video_conference_rooms')->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reopened_from_session_id')->nullable()->constrained('video_conference_sessions')->nullOnDelete();
            $table->string('status')->default('live');
            $table->timestamp('started_at');
            $table->timestamp('auto_end_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ended_reason')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'vcs_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_conference_sessions');
    }
};
