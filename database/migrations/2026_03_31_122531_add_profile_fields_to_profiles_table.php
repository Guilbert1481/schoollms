<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('profile_type')->after('user_id')->nullable();
            $table->string('profile_code')->after('profile_type')->nullable();
            $table->unsignedBigInteger('school_id')->after('profile_code')->nullable();
            $table->string('status')->default('active')->after('school_id');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'profile_type',
                'profile_code',
                'school_id',
                'status'
            ]);
        });
    }
};
