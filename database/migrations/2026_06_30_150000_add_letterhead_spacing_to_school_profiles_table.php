<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vertical space (in inches) reserved on printed documents for the header
     * and footer bands. Defaults: 1" header, 0.5" footer. These define how much
     * room the header/footer image (or default band) gets on the paper.
     */
    public function up(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->decimal('header_space', 4, 2)->default(1.00)->after('school_background');
            $table->decimal('footer_space', 4, 2)->default(0.50)->after('header_space');
        });
    }

    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->dropColumn(['header_space', 'footer_space']);
        });
    }
};
