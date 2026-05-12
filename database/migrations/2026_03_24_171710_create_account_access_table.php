<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_access', function (Blueprint $table) {
            $table->id();

            // Account (users table)
            $table->unsignedBigInteger('user_id');

            // Person (people table or profiles table)
            $table->unsignedBigInteger('person_id');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Admin who transferred access
            $table->unsignedBigInteger('assigned_by')->nullable();

            $table->text('remarks')->nullable();

            $table->boolean('is_active')->default(1);

            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('assigned_by')->references('id')->on('users');
            // If using people table:
            // $table->foreign('person_id')->references('id')->on('people');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_access');
    }
};