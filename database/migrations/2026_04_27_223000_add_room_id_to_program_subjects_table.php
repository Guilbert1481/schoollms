<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_subjects') && ! Schema::hasColumn('program_subjects', 'room_id')) {
            Schema::table('program_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('room_id')->nullable()->after('subject_id');
                $table->index('room_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_subjects') && Schema::hasColumn('program_subjects', 'room_id')) {
            Schema::table('program_subjects', function (Blueprint $table) {
                $table->dropIndex(['room_id']);
                $table->dropColumn('room_id');
            });
        }
    }
};
