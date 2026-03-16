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
    Schema::table('test_sources', function (Blueprint $table) {
        $table->unsignedBigInteger('test_id')->after('id');
        $table->string('source_type')->after('test_id');
        $table->unsignedBigInteger('source_id')->after('source_type');

        $table->integer('mcq_count')->default(0);
        $table->integer('tf_count')->default(0);
        $table->integer('mtf_count')->default(0);
        $table->integer('identification_count')->default(0);
        $table->integer('matching_count')->default(0);
        $table->integer('fib_count')->default(0);
        $table->integer('enumeration_count')->default(0);
        $table->integer('essay_count')->default(0);
    });
}

public function down()
{
    Schema::table('test_sources', function (Blueprint $table) {
        $table->dropColumn([
            'test_id',
            'source_type',
            'source_id',
            'mcq_count',
            'tf_count',
            'mtf_count',
            'identification_count',
            'matching_count',
            'fib_count',
            'enumeration_count',
            'essay_count',
        ]);
    });
}

};
