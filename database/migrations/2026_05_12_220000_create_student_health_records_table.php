<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_health_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            // Q1 — does the student have any condition?
            $table->boolean('has_medical_condition')->default(false);

            // Multi-select stored as JSON: ['asthma','diabetes',...]
            $table->json('medical_conditions')->nullable();
            $table->string('other_medical_condition', 255)->nullable();

            // Allergies free-text
            $table->text('allergies')->nullable();

            // Maintenance medication
            $table->boolean('takes_maintenance_medication')->default(false);
            $table->string('medication_name', 255)->nullable();
            $table->string('medication_dosage', 255)->nullable();
            $table->string('medication_schedule', 255)->nullable();

            // Blood type
            $table->enum('blood_type', [
                'A+','A-','B+','B-','AB+','AB-','O+','O-','unknown',
            ])->nullable();

            // Emergency medical instructions
            $table->text('emergency_medical_instructions')->nullable();

            // PWD
            $table->boolean('is_pwd')->default(false);
            $table->string('pwd_type', 191)->nullable();
            $table->string('pwd_id_number', 100)->nullable();

            // Family doctor
            $table->string('doctor_name', 191)->nullable();
            $table->string('doctor_contact', 50)->nullable();

            // Health-specific emergency contact (separate from guardian)
            $table->string('emergency_contact_name', 191)->nullable();
            $table->string('emergency_contact_relationship', 100)->nullable();
            $table->string('emergency_contact_mobile', 50)->nullable();
            $table->string('emergency_contact_alt', 50)->nullable();

            $table->timestamps();

            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_records');
    }
};
