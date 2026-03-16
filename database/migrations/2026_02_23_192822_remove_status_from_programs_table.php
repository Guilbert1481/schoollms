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
        Schema::table('programs', function (Blueprint $table) {
            // Drop the redundant status column
            $table->dropColumn('status');
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            // In case you need to rollback, add it back
            $table->string('status')->nullable();
        });
    }
};
