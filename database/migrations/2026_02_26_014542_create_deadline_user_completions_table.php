<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_user_completions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('deadline_id');
            $table->unsignedBigInteger('user_id');

            $table->boolean('is_completed')->default(false);
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('deadline_id')
                  ->references('id')
                  ->on('deadlines')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            /*
            |--------------------------------------------------------------------------
            | Unique Constraint (CRITICAL)
            |--------------------------------------------------------------------------
            */

            $table->unique(['deadline_id', 'user_id'], 'deadline_user_unique');

            /*
            |--------------------------------------------------------------------------
            | Performance Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(['user_id', 'deadline_id'], 'idx_user_deadline');
            $table->index(['deadline_id', 'status'], 'idx_deadline_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_user_completions');
    }
};