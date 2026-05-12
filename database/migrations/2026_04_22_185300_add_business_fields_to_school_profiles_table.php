<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('established_year');
            $table->string('tax_number')->nullable()->after('business_type');
            $table->string('sss_number')->nullable()->after('tax_number');
            $table->string('business_permit_number')->nullable()->after('sss_number');
        });
    }

    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'tax_number', 'sss_number', 'business_permit_number']);
        });
    }
};
