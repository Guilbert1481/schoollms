<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('school_id')->after('id');
        });
    }

    public function down()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });
    }
};