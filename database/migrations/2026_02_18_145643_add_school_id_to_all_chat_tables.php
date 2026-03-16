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
    $tables = ['chats', 'chat_messages', 'chat_threads'];

    foreach ($tables as $tableName) {
        if (!Schema::hasColumn($tableName, 'school_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
            });
        }
    }

    // Handle pivot separately
    if (!Schema::hasColumn('chat_thread_user', 'school_id')) {
        Schema::table('chat_thread_user', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable();
        });
    }
}
};
