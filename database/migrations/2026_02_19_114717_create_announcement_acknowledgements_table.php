<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('announcement_acknowledgements', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('announcement_id');
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('school_id');

        $table->timestamp('acknowledged_at')->nullable();

        $table->timestamps();

        $table->unique(['announcement_id', 'user_id']);

        $table->foreign('announcement_id')
              ->references('id')
              ->on('announcements')
              ->onDelete('cascade');

        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('announcement_acknowledgements');
}

};
