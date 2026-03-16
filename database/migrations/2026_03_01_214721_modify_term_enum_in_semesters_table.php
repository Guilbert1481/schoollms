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
    DB::statement("
        ALTER TABLE semesters
        MODIFY term ENUM('first','second','third','summer','none') NOT NULL
    ");
}

public function down()
{
    DB::statement("
        ALTER TABLE semesters
        MODIFY term ENUM('first','second','third','summer') NOT NULL
    ");
}

};
