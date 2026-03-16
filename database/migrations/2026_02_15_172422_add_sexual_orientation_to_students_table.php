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
    Schema::table('students', function (Blueprint $table) {
        // Adding only the missing demographic field
        $table->string('sexual_orientation')->nullable()->after('gender');
    });
}

public function down()
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn('sexual_orientation');
    });
}
};
