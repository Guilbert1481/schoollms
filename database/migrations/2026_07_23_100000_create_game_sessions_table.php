<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A live multiplayer Gamified-Quiz session (Tug-of-War first).
 *
 * The host (teacher or student) opens a lobby, players join by code on their
 * own devices, then a fixed set of questions is drawn from the school's bank
 * and PERSISTED into game_session_questions so grading is server-authoritative
 * (the correct answer never leaves the server until reveal) and refresh /
 * late-join can resync. Tenant-scoped by school_id (see BelongsToSchool).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();

            $table->string('slug', 40)->default('tug-of-war');   // which game template
            $table->string('join_code', 12)->unique();           // players enter this
            $table->string('mode', 16)->default('team');         // solo | opponent | team
            $table->string('question_type', 24)->default('mcq'); // mcq | identification
            $table->string('difficulty', 16)->default('mixed');  // average | advanced | mixed
            $table->unsignedTinyInteger('question_count')->default(10);
            $table->unsignedSmallInteger('round_seconds')->default(20);

            // Content scope (mirrors game_quiz_settings / the ☰ menu).
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained('competencies')->nullOnDelete();

            // Live match state.
            $table->string('status', 16)->default('lobby');      // lobby | active | ended
            $table->unsignedSmallInteger('current_sequence')->default(0); // 1-based when active
            $table->smallInteger('rope_position')->default(0);   // + = Team A (blue), - = Team B (orange)
            $table->unsignedSmallInteger('team_a_score')->default(0);
            $table->unsignedSmallInteger('team_b_score')->default(0);
            $table->unsignedSmallInteger('team_a_correct')->default(0);
            $table->unsignedSmallInteger('team_b_correct')->default(0);
            $table->string('team_a_label', 40)->default('Team Einstein');
            $table->string('team_b_label', 40)->default('Team Newton');
            $table->timestamp('question_ends_at')->nullable();
            $table->string('winner', 8)->nullable();             // A | B | draw
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
