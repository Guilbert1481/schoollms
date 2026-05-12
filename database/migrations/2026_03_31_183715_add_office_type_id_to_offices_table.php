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
    Schema::table('offices', function (Blueprint $table) {
        $table->foreignId('office_type_id')
              ->nullable()
              ->after('name')
              ->constrained('office_types')
              ->nullOnDelete();
    });
}

public function down()
{
    Schema::table('offices', function (Blueprint $table) {
        $table->dropForeign(['office_type_id']);
        $table->dropColumn('office_type_id');
    });
}
};
