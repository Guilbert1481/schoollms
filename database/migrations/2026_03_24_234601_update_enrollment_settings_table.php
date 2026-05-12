<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {

            // For multiple enrollment types (Regular, Seminar, Training, Review)
            $table->foreignId('enrollment_type_id')
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            // Title for seminars, trainings, review classes
            $table->string('title')->nullable()->after('term_id');

            // Optional description
            $table->text('description')->nullable()->after('title');

        });
    }

    public function down(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {

            $table->dropForeign(['enrollment_type_id']);
            $table->dropColumn([
                'enrollment_type_id',
                'title',
                'description'
            ]);

        });
    }
};