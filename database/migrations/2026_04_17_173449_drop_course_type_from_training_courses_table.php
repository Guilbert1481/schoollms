<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_courses', function (Blueprint $table) {
            $table->dropColumn('course_type');
        });
    }

    public function down(): void
    {
        Schema::table('training_courses', function (Blueprint $table) {
            $table->string('course_type')->nullable();
        });
    }
};