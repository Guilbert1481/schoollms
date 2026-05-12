<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'scope')) {
                $table->enum('scope', ['academic', 'training'])
                    ->default('academic')
                    ->index();
            }
        });

        // Backfill: everything existing is academic.
        DB::table('subjects')->whereNull('scope')->update(['scope' => 'academic']);
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
