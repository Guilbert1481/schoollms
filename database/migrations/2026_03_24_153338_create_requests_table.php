<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            // School scope
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Who requested
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            // Request classification
            $table->string('type');      // role_assignment, grade_change, etc.
            $table->string('category');  // academic, hr, accounting, admin

            // Basic info
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Flexible data storage
            $table->json('data')->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'in_review',
                'approved',
                'rejected',
                'cancelled',
                'completed'
            ])->default('pending');

            // Priority
            $table->string('priority')->default('normal'); // low, normal, high, urgent

            // Dates
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};