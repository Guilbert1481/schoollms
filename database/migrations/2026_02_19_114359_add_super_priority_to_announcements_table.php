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
    Schema::table('announcements', function (Blueprint $table) {


        $table->enum('priority_level', ['normal', 'super'])
              ->default('normal')
              ->after('content');

        $table->timestamp('super_priority_expires_at')
              ->nullable()
              ->after('priority_level');
    });
}

public function down()
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->dropColumn([
            'priority_level',
            'super_priority_expires_at'
        ]);
    });
}

};
