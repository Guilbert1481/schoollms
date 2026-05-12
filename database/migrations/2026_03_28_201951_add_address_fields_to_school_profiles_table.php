<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('unit_number')->nullable();
            $table->string('building')->nullable();
            $table->string('phase')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            //
        });
    }
};
