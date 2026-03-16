<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // 1. Drop the foreign key first
            $table->dropForeign('topics_subject_id_foreign');

            // 2. Drop the incorrect unique index
            $table->dropUnique('topics_subject_id_title_unique');

            // 3. Recreate the correct foreign key (no unique constraint)
            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->onDelete('cascade');

            // 4. (Optional) Add a proper index for performance
            $table->index(['subject_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Reverse operations if needed
            $table->dropIndex(['subject_id', 'name']);
            $table->dropForeign('topics_subject_id_foreign');
            $table->unique(['subject_id'], 'topics_subject_id_title_unique');
            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->onDelete('cascade');
        });
    }
};

