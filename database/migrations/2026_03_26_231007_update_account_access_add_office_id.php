<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('account_access', function (Blueprint $table) {
        $table->unsignedBigInteger('office_id')->nullable()->after('person_id');

        $table->dropColumn(['department_id', 'program_id']);
    });
}

public function down()
{
    Schema::table('account_access', function (Blueprint $table) {
        $table->unsignedBigInteger('department_id')->nullable();
        $table->unsignedBigInteger('program_id')->nullable();

        $table->dropColumn('office_id');
    });
}
};
