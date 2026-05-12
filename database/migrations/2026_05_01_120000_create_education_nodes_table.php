<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_nodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('node_type'); // level | stage | track | strand | program_type
            $table->integer('order_index')->default(0);
            $table->boolean('is_offered')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('education_nodes')
                ->cascadeOnDelete();

            $table->index('parent_id');
            $table->index('node_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_nodes');
    }
};
