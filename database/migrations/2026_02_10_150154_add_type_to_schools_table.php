<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('schools', function (Blueprint $table) {
        // We use 'school_name' here because that is what exists in your database
        $table->string('type')->default('school')->after('school_name');
    });
}

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};