<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSemestersTable extends Migration
{
    public function up()
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->string('academic_year', 20);

            $table->enum('term', [
                'first',
                'second',
                'third',
                'summer'
            ]);

            $table->string('name', 100)->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['school_id', 'academic_year', 'term']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('semesters');
    }
}