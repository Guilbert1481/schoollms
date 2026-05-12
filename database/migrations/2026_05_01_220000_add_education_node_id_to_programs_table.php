<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('education_node_id')->nullable()->after('campus_id');
            $table->foreign('education_node_id')
                ->references('id')->on('education_nodes')
                ->nullOnDelete();
            $table->index('education_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['education_node_id']);
            $table->dropIndex(['education_node_id']);
            $table->dropColumn('education_node_id');
        });
    }
};
