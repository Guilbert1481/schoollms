<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link an academic year / term to its education level in the Education
     * Structure Tree (a root education_node). The legacy string `education_level`
     * ('higher_ed' | 'basic_ed') is kept and auto-synced from this node by the
     * model, so the ~47 existing consumers keep working untouched.
     */
    public function up(): void
    {
        foreach (['academic_years', 'terms'] as $tbl) {
            if (! Schema::hasColumn($tbl, 'education_node_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->foreignId('education_node_id')
                        ->nullable()
                        ->after('education_level')
                        ->constrained('education_nodes')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['academic_years', 'terms'] as $tbl) {
            if (Schema::hasColumn($tbl, 'education_node_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropForeign([$tbl === 'terms' ? 'terms_education_node_id_foreign' : 'academic_years_education_node_id_foreign']);
                    $table->dropColumn('education_node_id');
                });
            }
        }
    }
};
