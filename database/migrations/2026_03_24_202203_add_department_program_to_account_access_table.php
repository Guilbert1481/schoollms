<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('account_access', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('person_id');
            $table->unsignedBigInteger('program_id')->nullable()->after('department_id');
            $table->string('role_snapshot')->nullable()->after('program_id');
        });
    }

    public function down()
    {
        Schema::table('account_access', function (Blueprint $table) {
            $table->dropColumn(['department_id', 'program_id', 'role_snapshot']);
        });
    }
};