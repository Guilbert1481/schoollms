<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which education levels a staff user is responsible for.
     *
     * Added for the Course Architect: one architect authors Basic Education while
     * another handles the higher-ed programs, and Lesson Studio must show each of
     * them only their own catalogue. Rows point at TOP-LEVEL education_nodes roots
     * (Basic Education, Undergraduate Programs, …); a subject belongs to a root via
     * `is_basic_ed` for Basic Ed or via program_subjects → programs.education_node_id
     * for the rest.
     *
     * NO rows for a user means UNRESTRICTED — every existing user keeps seeing
     * everything, so this can ship without assigning anyone first.
     */
    public function up(): void
    {
        Schema::create('user_education_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_node_id')->constrained('education_nodes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'education_node_id']);
            $table->index('education_node_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_education_scopes');
    }
};
