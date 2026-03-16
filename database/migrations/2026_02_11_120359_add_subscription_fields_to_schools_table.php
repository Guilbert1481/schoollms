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
        Schema::table('schools', function (Blueprint $table) {
            // Defines the active tier for the school
            $table->string('plan_name')->default('basic')->after('type'); 
            
            // Tracks the end of the 1-month free trial
            $table->timestamp('plan_expires_at')->nullable()->after('plan_name');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['plan_name', 'plan_expires_at']);
        });
    }
};
