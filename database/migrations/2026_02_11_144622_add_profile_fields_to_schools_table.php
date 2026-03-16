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
        Schema::table('schools', function (Blueprint $table) {
    // Drop FK first
    if (Schema::hasColumn('schools', 'pricing_id')) {
        try {
            $table->dropForeign(['pricing_id']);
        } catch (\Exception $e) {
            // ignore if already dropped
        }
    }
});

    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
    if (Schema::hasColumn('schools', 'pricing_id')) {
        $table->dropColumn('pricing_id');
    }
});

    }
};
