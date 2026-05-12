<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_offerings', function (Blueprint $table) {
            $table->id();
            
            // The Bridge: Linking Master Subject to a specific Term
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            
            // Unique Code for the Batch/Section (e.g., ENG101-ONLINE-2026)
            $table->string('offering_code')->unique();
            
            // The Switch: How is this being taught?
            $table->enum('delivery_mode', ['pure_online', 'online_teacher', 'face_to_face'])
                  ->default('pure_online');
            
            // Optional: Link a teacher if not Pure Online
            $table->foreignId('teacher_id')->nullable()->constrained('users');
            
            // Capacity Management
            $table->integer('max_slots')->nullable();
            $table->integer('enrolled_count')->default(0);
            
            // Operational Status
            $table->enum('status', ['draft', 'open', 'ongoing', 'closed'])->default('draft');

            $table->timestamps();
            $table->softDeletes(); // Good for auditing school records
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_offerings');
    }
};