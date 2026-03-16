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
    Schema::create('test_settings', function (Blueprint $table) {
        $table->id();

        $table->foreignId('test_id')
            ->constrained('tests')
            ->cascadeOnDelete();

        $table->integer('timer_minutes')->nullable();
        $table->integer('attempts_allowed')->default(1);
        $table->integer('passing_score')->default(75);

        $table->string('show_results')->default('after_exam');
        $table->string('show_correct_answers')->default('after_exam');

        $table->boolean('shuffle_questions')->default(false);
        $table->boolean('shuffle_mcq_choices')->default(false);

        $table->timestamp('start_at')->nullable();
        $table->timestamp('end_at')->nullable();

        $table->string('mode')->nullable();
        $table->string('term')->nullable();
        $table->string('assessment_type')->nullable();

        $table->timestamps();
    });
}

};
