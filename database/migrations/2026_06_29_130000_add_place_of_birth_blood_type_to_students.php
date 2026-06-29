<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'place_of_birth')) {
                $table->string('place_of_birth', 255)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('students', 'blood_type')) {
                $table->string('blood_type', 8)->nullable()->after('place_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['place_of_birth', 'blood_type'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
