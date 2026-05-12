<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculums', function (Blueprint $table) {
            // 2 = bi-semester (1st, 2nd)
            // 3 = tri-semester / trimester (1st, 2nd, 3rd)
            $table->unsignedTinyInteger('terms_per_year')->default(2)->after('version');
        });
    }

    public function down(): void
    {
        Schema::table('curriculums', function (Blueprint $table) {
            $table->dropColumn('terms_per_year');
        });
    }
};
