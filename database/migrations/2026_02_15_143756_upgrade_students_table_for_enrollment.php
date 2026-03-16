<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            // Identity
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('preferred_name')->nullable()->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');

            $table->date('date_of_birth')->nullable()->after('suffix');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('nationality')->nullable()->after('gender');
            $table->string('civil_status')->nullable()->after('nationality');
            $table->string('religion')->nullable()->after('civil_status');

            // Government
            $table->string('government_id_type')->nullable()->after('religion');
            $table->string('government_id_number')->nullable()->after('government_id_type');

            // Profile
            $table->string('photo_path')->nullable()->after('government_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name',
                'preferred_name',
                'suffix',
                'date_of_birth',
                'gender',
                'nationality',
                'civil_status',
                'religion',
                'government_id_type',
                'government_id_number',
                'photo_path'
            ]);
        });
    }
};
