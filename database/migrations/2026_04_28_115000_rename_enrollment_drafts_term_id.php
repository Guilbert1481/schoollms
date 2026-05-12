<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollment_drafts')) {
            return;
        }

        if (Schema::hasColumn('enrollment_drafts', 'academic_term_id') && ! Schema::hasColumn('enrollment_drafts', 'term_id')) {
            Schema::table('enrollment_drafts', function (Blueprint $table) {
                $table->renameColumn('academic_term_id', 'term_id');
            });
        }

        Schema::table('enrollment_drafts', function (Blueprint $table) {
            try {
                $table->foreign('term_id')
                    ->references('id')->on('terms')
                    ->cascadeOnDelete();
            } catch (\Throwable $e) {
                // FK may already exist.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('enrollment_drafts')) {
            return;
        }

        Schema::table('enrollment_drafts', function (Blueprint $table) {
            try { $table->dropForeign(['term_id']); } catch (\Throwable $e) {}
        });

        if (Schema::hasColumn('enrollment_drafts', 'term_id') && ! Schema::hasColumn('enrollment_drafts', 'academic_term_id')) {
            Schema::table('enrollment_drafts', function (Blueprint $table) {
                $table->renameColumn('term_id', 'academic_term_id');
            });
        }
    }
};
