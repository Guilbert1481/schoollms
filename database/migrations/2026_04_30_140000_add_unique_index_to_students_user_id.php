<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Enforce a single Student row per User. The duplicate that previously
     * crept in (caused by a missing User::student() relation) has already
     * been cleaned up; this index makes a regression impossible at the DB
     * level. user_id is nullable so MySQL still allows multiple NULLs.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unique('user_id', 'students_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_user_id_unique');
        });
    }
};
