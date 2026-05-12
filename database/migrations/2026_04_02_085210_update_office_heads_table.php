<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('office_heads', function (Blueprint $table) {
            $table->string('position')->nullable()->after('name');
            $table->string('email')->nullable()->after('position');
            $table->string('contact_number')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('office_heads', function (Blueprint $table) {
            $table->dropColumn(['position','email','contact_number']);
        });
    }
};