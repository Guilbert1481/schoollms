<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->unsignedBigInteger('office_head_id')->nullable()->after('name');

            $table->foreign('office_head_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropForeign(['office_head_id']);
            $table->dropColumn('office_head_id');
        });
    }
};