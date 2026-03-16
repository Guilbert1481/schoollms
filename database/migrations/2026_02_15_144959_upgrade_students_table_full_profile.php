<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            // =========================
            // Additional Identity
            // =========================
            $table->string('place_of_birth')->nullable()->after('date_of_birth');

            // =========================
            // Contact
            // =========================
            $table->string('mobile_number')->nullable()->after('email');
            $table->string('landline_number')->nullable()->after('mobile_number');

            // =========================
            // Structured Address
            // =========================
            $table->string('unit_number')->nullable();
            $table->string('building_name')->nullable();
            $table->string('street')->nullable();
            $table->string('subdivision')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('zip_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'place_of_birth',
                'mobile_number',
                'landline_number',
                'unit_number',
                'building_name',
                'street',
                'subdivision',
                'barangay',
                'city_municipality',
                'province',
                'region',
                'zip_code',
            ]);
        });
    }
};
