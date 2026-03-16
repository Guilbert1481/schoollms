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
        Schema::table('pricings', function (Blueprint $table) {
            // Add plan_name and make it unique so we don't have duplicate Basic plans
            $table->string('plan_name')->after('id')->unique();
            
            // Change existing columns to decimal for financial accuracy (if they aren't already)
            $table->decimal('student_price', 10, 2)->default(0)->change();
            $table->decimal('teacher_price', 10, 2)->default(0)->change();
            // ... repeat for other price columns ...
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            //
        });
    }
};
