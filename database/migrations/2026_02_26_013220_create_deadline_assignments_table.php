<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_assignments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('deadline_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('assignable_type');
            $table->unsignedBigInteger('assignable_id');

            $table->foreignId('assigned_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('visible')->default(true);

            $table->timestamps();

            // REQUIRED UNIQUE (Polymorphic Safety)
            $table->unique(
                ['deadline_id', 'assignable_type', 'assignable_id'],
                'deadline_assignments_unique'
            );

            // Performance indexes
            $table->index(['assignable_type', 'assignable_id']);
            $table->index(['school_id', 'deadline_id']);
            $table->index(['deadline_id', 'visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_assignments');
    }
};