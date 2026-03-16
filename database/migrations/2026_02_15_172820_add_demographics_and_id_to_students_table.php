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
    Schema::table('students', function (Blueprint $table) {
        
        // Adding the ID document path after the profile photo path
        $table->string('photo_id')->nullable()->after('photo_path');
    });
}

public function down()
{
    
}
};
