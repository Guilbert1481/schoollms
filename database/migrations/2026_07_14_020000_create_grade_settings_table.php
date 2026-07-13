<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-school grade settings, managed by the Principal (Settings → Grades).
 * Home for every grading threshold/policy the Principal sets. First up: the
 * passing threshold and the promotion rule the Form 137 standing uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->decimal('passing_threshold', 5, 2)->default(75.00);
            // 'average'        → Promoted when the General Average ≥ threshold
            // 'all_areas_pass' → also requires no failed learning area
            $table->string('promotion_rule', 32)->default('average');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_settings');
    }
};
