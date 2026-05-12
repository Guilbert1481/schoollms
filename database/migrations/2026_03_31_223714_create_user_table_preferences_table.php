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
        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('table_key'); // unique per page/table

            $table->json('column_settings')->nullable(); 
            // stores visibility, width, order of columns

            $table->integer('rows_per_page')->default(10);

            $table->string('sort_column')->nullable();
            $table->string('sort_direction')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'table_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_table_preferences');
    }
};