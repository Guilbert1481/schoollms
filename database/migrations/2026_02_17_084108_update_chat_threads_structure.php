<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {

            // Replace is_group with structured type
            $table->enum('type', [
                'private',
                'group',
                'department',
                'class'
            ])->default('private')->after('name');

            $table->unsignedBigInteger('department_id')->nullable()->after('type');
            $table->unsignedBigInteger('class_id')->nullable()->after('department_id');

            $table->unsignedBigInteger('school_id')->nullable()->after('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'department_id',
                'class_id',
                'school_id'
            ]);
        });
    }
};
