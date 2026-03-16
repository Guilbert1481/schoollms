<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {

            // Add missing columns only if they don't exist
            if (!Schema::hasColumn('announcements', 'target_type')) {
                $table->string('target_type')->default('all')->after('content');
            }

            if (!Schema::hasColumn('announcements', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            }

            if (!Schema::hasColumn('announcements', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('announcements', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('published_at');
            }

            if (!Schema::hasColumn('announcements', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->index('target_type');
            $table->index('target_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'target_type',
                'target_id',
                'published_at',
                'expires_at',
                'deleted_at'
            ]);
        });
    }
};
