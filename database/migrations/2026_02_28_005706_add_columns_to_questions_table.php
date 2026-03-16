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
        // Adding question_text since it was missing in your error log
        if (!Schema::hasColumn('questions', 'question_text')) {
            $table->text('question_text')->after('question_type');
        }

        // Adding keyword specifically after difficulty
        $table->string('keyword')->nullable()->after('difficulty');
    });
}

public function down(): void
{
    Schema::table('questions', function (Blueprint $table) {
        $table->dropColumn(['question_text', 'keyword']);
    });
}
};
