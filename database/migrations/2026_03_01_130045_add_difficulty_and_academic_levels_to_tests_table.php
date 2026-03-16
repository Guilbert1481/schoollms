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
        $table->json('difficulty')->nullable()->after('topic_id');
        $table->json('academic_levels')->nullable()->after('difficulty');
    });
}

public function down()
{
    Schema::table('tests', function (Blueprint $table) {
        $table->dropColumn(['difficulty', 'academic_levels']);
    });
}

};
