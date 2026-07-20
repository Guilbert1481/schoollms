<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Basic-ed sections belong to a grade level (education_nodes), not a
     * program. Exactly one of program_id / education_node_id is meant to be
     * set; both stay nullable so higher-ed rows are untouched.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (! Schema::hasColumn('sections', 'education_node_id')) {
                $table->foreignId('education_node_id')
                    ->nullable()
                    ->after('program_id')
                    ->constrained('education_nodes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'education_node_id')) {
                $table->dropConstrainedForeignId('education_node_id');
            }
        });
    }
};
