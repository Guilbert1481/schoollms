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
        // Removing the unnecessary column
        $table->dropColumn('place_of_birth');
    });
}

public function down()
{
    Schema::table('students', function (Blueprint $table) {
        // Adding it back in case you need to rollback
        $table->string('place_of_birth')->nullable()->after('date_of_birth');
    });
}
};
