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
    Schema::table('tests', function (Blueprint $table) {
        $table->string('assessment_type')->nullable()->after('academic_levels');
    });
}

public function down()
{
    Schema::table('tests', function (Blueprint $table) {
        $table->dropColumn('assessment_type');
    });
}

};
