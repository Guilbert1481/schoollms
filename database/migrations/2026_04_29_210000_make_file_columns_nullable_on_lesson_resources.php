<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('file_type', 32)->nullable()->change();
            $table->string('original_filename')->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->string('file_path')->nullable(false)->change();
            $table->string('file_type', 32)->nullable(false)->change();
            $table->string('original_filename')->nullable(false)->change();
            $table->unsignedBigInteger('file_size')->nullable(false)->change();
        });
    }
};
