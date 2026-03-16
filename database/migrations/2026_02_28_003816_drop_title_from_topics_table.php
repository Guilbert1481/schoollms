<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('topics', function (Blueprint $table) {
        // This removes the redundant 'title' column
        $table->dropColumn('title');
    });
}

public function down(): void
{
    Schema::table('topics', function (Blueprint $table) {
        // This allows you to roll back if needed
        $table->string('title')->nullable();
    });
}
};
