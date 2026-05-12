<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add capacity to sections (model already declares it; column was missing)
        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections', 'capacity')) {
                $table->integer('capacity')->default(0)->after('year_level');
            }
        });

        // 2. Add home_school_id to students for cross-enrollees
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'home_school_id')) {
                $table->foreignId('home_school_id')
                      ->nullable()
                      ->after('school_id')
                      ->constrained('schools')
                      ->nullOnDelete();
            }
        });

        // 3. Subject equivalencies between home and host schools
        Schema::create('cross_school_subject_equivalencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('home_school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            $table->foreignId('host_school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            $table->foreignId('home_subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->foreignId('host_subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['home_school_id', 'host_school_id', 'home_subject_id', 'host_subject_id'],
                'idx_cross_subj_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_school_subject_equivalencies');

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'home_school_id')) {
                $table->dropForeign(['home_school_id']);
                $table->dropColumn('home_school_id');
            }
        });

        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};
