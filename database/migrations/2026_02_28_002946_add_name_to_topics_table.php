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
        // Adding the name column which your controller needs
        $table->string('name')->after('subject_id'); 
    });
}

public function down(): void
{
    Schema::table('topics', function (Blueprint $table) {
        $table->dropColumn('name');
    });
}
};
