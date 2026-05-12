<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->string('term', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->enum('term', ['first','second','third','summer','none'])->change();
        });
    }
};

