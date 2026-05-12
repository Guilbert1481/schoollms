<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            if (! Schema::hasColumn('topics', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('name')->index();
            }
        });

        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('name')->index();
            }
        });

        // Seed initial order from existing IDs so cards render predictably.
        foreach (DB::table('topics')->select('subject_id')->distinct()->pluck('subject_id') as $sid) {
            $i = 1;
            foreach (DB::table('topics')->where('subject_id', $sid)->orderBy('id')->pluck('id') as $id) {
                DB::table('topics')->where('id', $id)->update(['sort_order' => $i++]);
            }
        }
        foreach (DB::table('lessons')->select('topic_id')->distinct()->pluck('topic_id') as $tid) {
            $i = 1;
            foreach (DB::table('lessons')->where('topic_id', $tid)->orderBy('id')->pluck('id') as $id) {
                DB::table('lessons')->where('id', $id)->update(['sort_order' => $i++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            if (Schema::hasColumn('topics', 'sort_order')) $table->dropColumn('sort_order');
        });
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'sort_order')) $table->dropColumn('sort_order');
        });
    }
};
