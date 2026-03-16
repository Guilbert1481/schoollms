<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->integer('year_level');
            $table->integer('semester_number'); // 1,2,3 (if summer)

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Fix: Added 'program_subject_unique' as a custom short index name
            $table->unique([
                'program_id',
                'subject_id',
                'year_level',
                'semester_number'
            ], 'program_subject_unique'); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_subjects');
    }
};