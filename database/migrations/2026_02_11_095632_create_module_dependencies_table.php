<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_dependencies', function (Blueprint $table) {
            $table->id();

            // The module that has dependencies
            $table->unsignedBigInteger('module_id');

            // The module it depends on
            $table->unsignedBigInteger('depends_on_module_id');

            $table->timestamps();

            // Foreign keys
            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');

            $table->foreign('depends_on_module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');

            // Prevent duplicate dependency rows
            $table->unique(['module_id', 'depends_on_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_dependencies');
    }
};
