<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_conference_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('context_name')->nullable();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('permission_id')->nullable()->constrained('video_conference_permissions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedSmallInteger('auto_end_minutes')->default(180);
            $table->timestamps();

            $table->index(['school_id', 'status'], 'vcr_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_conference_rooms');
    }
};
