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
    Schema::table('questions', function (Blueprint $table) {
        $table->dropColumn('question');
    });
}

public function down(): void
{
    Schema::table('questions', function (Blueprint $table) {
        // In case of rollback, we'll add it back as a text field
        $table->text('question')->nullable();
    });
}
};
