<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colleges', function (Blueprint $table) {

            $table->unsignedBigInteger('school_id')->after('id');

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('colleges', function (Blueprint $table) {

            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');

        });
    }
};