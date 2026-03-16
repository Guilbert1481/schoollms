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
        // Drop the foreign key first
        $table->dropForeign(['topic_id']);

        // Then drop the column
        $table->dropColumn('topic_id');
    });
}

public function down()
{
    Schema::table('tests', function (Blueprint $table) {
        // Restore the column
        $table->unsignedBigInteger('topic_id')->nullable();

        // Restore the foreign key
        $table->foreign('topic_id')->references('id')->on('topics')->onDelete('cascade');
    });
}

};
