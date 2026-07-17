<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            // Seed for the print "arrangement". Question order is derived
            // deterministically from this, so the questionnaire, answer key, and
            // OMR sheets all render the same shuffle. Null = natural (unshuffled).
            $table->unsignedBigInteger('print_seed')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('print_seed');
        });
    }
};
