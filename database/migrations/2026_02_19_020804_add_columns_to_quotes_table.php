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
    Schema::table('quotes', function (Blueprint $table) {
        // Adding the missing columns to the existing table
        $table->text('content')->after('id');
        $table->string('author')->nullable()->after('content');
        $table->string('theme')->after('author');
        $table->boolean('is_active')->default(false)->after('theme');
    });
}

public function down(): void
{
    Schema::table('quotes', function (Blueprint $table) {
        // This allows you to "rollback" these specific columns if needed
        $table->dropColumn(['content', 'author', 'theme', 'is_active']);
    });
}
};
