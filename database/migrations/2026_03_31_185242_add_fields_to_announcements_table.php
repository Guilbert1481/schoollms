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
        $table->string('announcement_type')->nullable()->after('content');
        $table->string('related_type')->nullable()->after('announcement_type');
        $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
        $table->boolean('is_active')->default(true)->after('expires_at');
    });
}

public function down()
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->dropColumn([
            'announcement_type',
            'related_type',
            'related_id',
            'is_active'
        ]);
    });
}
};
