<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_resources', 'sub_competency')) {
                $table->dropColumn('sub_competency');
            }
            if (Schema::hasColumn('lesson_resources', 'title')) {
                $table->dropColumn('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->string('sub_competency')->nullable();
            $table->string('title')->nullable();
        });
    }
};
