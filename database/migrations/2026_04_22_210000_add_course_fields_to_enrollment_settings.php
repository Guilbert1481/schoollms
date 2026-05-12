<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {

            if (!Schema::hasColumn('enrollment_settings', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('description');
            }

            if (!Schema::hasColumn('enrollment_settings', 'currency')) {
                $table->string('currency', 8)->nullable()->after('price');
            }

            if (!Schema::hasColumn('enrollment_settings', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('currency');
            }

            if (!Schema::hasColumn('enrollment_settings', 'instructor_title')) {
                $table->string('instructor_title', 50)->nullable()->after('cover_image');
            }

            if (!Schema::hasColumn('enrollment_settings', 'instructor_name')) {
                $table->string('instructor_name')->nullable()->after('instructor_title');
            }

            if (!Schema::hasColumn('enrollment_settings', 'course_details')) {
                $table->text('course_details')->nullable()->after('instructor_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {
            foreach (['course_details','instructor_name','instructor_title','cover_image','currency','price'] as $col) {
                if (Schema::hasColumn('enrollment_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
