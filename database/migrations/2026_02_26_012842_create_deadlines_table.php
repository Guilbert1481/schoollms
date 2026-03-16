<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {

            // Primary
            $table->id();

            // Relationships
            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Core Fields
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('type')->default('assignment');
            $table->dateTime('due_date');

            // Flags
            $table->boolean('requires_submission')->default(true);
            $table->boolean('allow_late_submission')->default(false);
            $table->boolean('active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Optimized Indexing
            |--------------------------------------------------------------------------
            */

            // Most common LMS queries
            $table->index(['school_id', 'active'], 'idx_school_active');
            $table->index(['school_id', 'due_date'], 'idx_school_due_date');
            $table->index(['school_id', 'created_by'], 'idx_school_creator');
            $table->index(['school_id', 'deleted_at'], 'idx_school_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};