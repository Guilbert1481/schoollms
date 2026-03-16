<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // chat_threads upgrades
        Schema::table('chat_threads', function (Blueprint $table) {

            $table->unsignedBigInteger('created_by')->after('school_id');

            $table->enum('status', ['active', 'pending_deletion'])
                  ->default('active')
                  ->after('created_by');

            $table->softDeletes();
        });

        // chat_messages upgrades
        Schema::table('chat_messages', function (Blueprint $table) {

            $table->boolean('deleted_by_user')
                  ->default(false)
                  ->after('message');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'status']);
            $table->dropSoftDeletes();
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['deleted_by_user']);
            $table->dropSoftDeletes();
        });
    }
};
