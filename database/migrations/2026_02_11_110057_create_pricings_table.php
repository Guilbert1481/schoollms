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
        Schema::create('pricings', function (Blueprint $table) {
            $table->id();
            $table->integer('student_price')->default(100);
            $table->integer('teacher_price')->default(120);
            $table->integer('staff_price')->default(80);
            $table->integer('parent_price')->default(30);
            $table->integer('alumni_price')->default(20);
            $table->integer('setup_fee')->default(20000);
            $table->integer('addon_price')->default(20);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricings');
    }
};
