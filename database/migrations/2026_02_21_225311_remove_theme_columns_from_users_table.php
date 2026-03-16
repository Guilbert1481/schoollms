<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sidebar_mode',
                'sidebar_style',
                'sidebar_color',
                'header_mode',
                'header_style',
                'header_color',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sidebar_mode')->nullable();
            $table->string('sidebar_style')->nullable();
            $table->string('sidebar_color')->nullable();
            $table->string('header_mode')->nullable();
            $table->string('header_style')->nullable();
            $table->string('header_color')->nullable();
        });
    }
};
