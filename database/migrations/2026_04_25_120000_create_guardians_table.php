<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('parent');         // parent|guardian|emergency
            $table->string('relationship', 64)->nullable();        // mother|father|aunt|grandparent|etc
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->string('mobile_number', 32)->nullable();
            $table->string('landline_number', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['student_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
