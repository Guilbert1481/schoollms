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
    Schema::table('test_settings', function (Blueprint $table) {
        $table->string('availability_mode')->default('duration')->after('end_at');
    });
}

public function down()
{
    Schema::table('test_settings', function (Blueprint $table) {
        $table->dropColumn(['duration_minutes', 'start_at', 'end_at', 'availability_mode']);
    });
}

};
