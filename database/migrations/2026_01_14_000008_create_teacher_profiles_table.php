<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('employee_id')->nullable();
            $table->string('department')->nullable();
            $table->string('specialization')->nullable();

            $table->enum('employment_status', [
                'active',
                'inactive',
                'on_leave',
                'resigned',
                'terminated'
            ])->default('active');

            $table->date('employment_start')->nullable();
            $table->date('employment_end')->nullable();

            $table->timestamps();

            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
