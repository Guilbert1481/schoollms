<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            // Link to person
            $table->unsignedBigInteger('profile_id');

            // Office assignment
            $table->unsignedBigInteger('office_id')->nullable();

            // Employment info
            $table->string('employee_number')->nullable();
            $table->string('position')->nullable();
            $table->string('employment_type')->nullable(); // Regular, Contractual, Part-time
            $table->string('employment_status')->default('Active');

            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();

            // Government numbers
            $table->string('tin')->nullable();
            $table->string('sss')->nullable();
            $table->string('philhealth')->nullable();
            $table->string('pagibig')->nullable();
            $table->string('bank_account')->nullable();

            // Documents
            $table->json('documents')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff');
    }
};