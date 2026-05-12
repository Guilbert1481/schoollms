<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'country')) {
                $table->string('country', 2)->nullable()->after('region');
            }
            if (! Schema::hasColumn('students', 'country_code')) {
                $table->string('country_code', 8)->nullable()->after('country');
            }
            if (! Schema::hasColumn('students', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('students', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['country', 'country_code', 'address_line_1', 'address_line_2']);
        });
    }
};
