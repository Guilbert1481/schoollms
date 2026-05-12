<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            // Resources are now subject-global; program scoping is informational.
            $table->foreignId('program_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable(false)->change();
        });
    }
};
