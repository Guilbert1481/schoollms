<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->integer('selected_subject_count')
                  ->default(0)
                  ->after('term_id');
        });
    }

    public function down()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('selected_subject_count');
        });
    }
};