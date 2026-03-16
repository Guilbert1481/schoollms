<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {

            // Whether admission test feature is enabled
            $table->boolean('requires_admission_test')
                  ->default(false)
                  ->after('primary_color');

            // Mode of admission test
            $table->enum('admission_test_mode', [
                'required',
                'optional',
                'diagnostic_only'
            ])
            ->nullable()
            ->after('requires_admission_test');

        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'requires_admission_test',
                'admission_test_mode'
            ]);
        });
    }
};
