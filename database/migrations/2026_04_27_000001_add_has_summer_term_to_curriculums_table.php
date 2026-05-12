<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('curriculums', function (Blueprint $table) {
            $table->boolean('has_summer_term')->default(false)->after('terms_per_year');
        });
    }
    public function down(): void
    {
        Schema::table('curriculums', function (Blueprint $table) {
            $table->dropColumn('has_summer_term');
        });
    }
};
