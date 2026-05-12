<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('training_enrollments', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('training_enrollments', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('expires_at');
            }

            if (!Schema::hasColumn('training_enrollments', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('training_enrollments', 'payment_paid_at')) {
                $table->timestamp('payment_paid_at')->nullable()->after('payment_reference');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'training_enrollment_id')) {
                $table->unsignedBigInteger('training_enrollment_id')->nullable()->after('student_id');
                $table->foreign('training_enrollment_id')
                    ->references('id')
                    ->on('training_enrollments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'training_enrollment_id')) {
                $table->dropForeign(['training_enrollment_id']);
                $table->dropColumn('training_enrollment_id');
            }
        });

        Schema::table('training_enrollments', function (Blueprint $table) {
            $columns = ['expires_at', 'payment_status', 'payment_reference', 'payment_paid_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('training_enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
