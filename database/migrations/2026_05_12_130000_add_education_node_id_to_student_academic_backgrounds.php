<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_academic_backgrounds', function (Blueprint $table) {
            $table->unsignedBigInteger('education_node_id')->nullable()->after('education_level');
            $table->index('education_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_academic_backgrounds', function (Blueprint $table) {
            $table->dropIndex(['education_node_id']);
            $table->dropColumn('education_node_id');
        });
    }
};
