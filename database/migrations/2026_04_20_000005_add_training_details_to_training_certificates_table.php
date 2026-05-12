<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_certificates', function (Blueprint $table) {
            $table->string('training_type_name')->nullable()->after('training_enrollment_id');
            $table->string('course_name')->nullable()->after('training_type_name');
        });
    }

    public function down(): void
    {
        Schema::table('training_certificates', function (Blueprint $table) {
            $table->dropColumn(['training_type_name', 'course_name']);
        });
    }
};
