<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            // Link to person
            $table->unsignedBigInteger('profile_id');

            // Employment info
            $table->string('employee_number')->nullable();
            $table->string('rank')->nullable(); // Instructor, Assistant Prof, etc.
            $table->string('employment_type')->nullable(); // Full-time / Part-time
            $table->string('employment_status')->default('Active');

            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();

            // Academic info
            $table->string('specialization')->nullable();
            $table->string('highest_degree')->nullable();
            $table->string('degree_school')->nullable();

            // Licenses and government numbers
            $table->string('prc_license')->nullable();
            $table->date('prc_expiry')->nullable();

            $table->string('tin')->nullable();
            $table->string('sss')->nullable();
            $table->string('philhealth')->nullable();
            $table->string('pagibig')->nullable();
            $table->string('bank_account')->nullable();

            // Teaching settings
            $table->integer('teaching_load_limit')->nullable();
            $table->boolean('adviser_flag')->default(false);
            $table->boolean('thesis_flag')->default(false);
            $table->boolean('research_flag')->default(false);

            // Documents (JSON file paths)
            $table->json('documents')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};