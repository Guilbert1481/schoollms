<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('training_type_id')->nullable()->after('course_name');

            $table->foreign('training_type_id')
                  ->references('id')
                  ->on('training_types')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_courses', function (Blueprint $table) {
            $table->dropForeign(['training_type_id']);
            $table->dropColumn('training_type_id');
        });
    }
};