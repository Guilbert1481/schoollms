<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_id_settings')) {
            return;
        }

        Schema::create('student_id_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->unique();
            // How the Student Digital ID is displayed — drives the Profile layout.
            $table->string('orientation', 16)->default('portrait');   // portrait | landscape
            $table->string('barcode_source', 24)->default('lrn');      // lrn | student_number
            $table->boolean('show_back')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_settings');
    }
};
