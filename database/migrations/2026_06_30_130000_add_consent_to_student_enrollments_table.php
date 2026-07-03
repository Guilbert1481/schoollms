<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit record of the legal consents captured at submission: who certified
     * (student / guardian), the accuracy acknowledgement, the RA 10173 data
     * privacy consent, and when the electronic confirmation was given.
     */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('certified_by', 20)->nullable()->after('agreed_to_penalty');
            $table->boolean('acknowledged_accuracy')->default(false)->after('certified_by');
            $table->boolean('data_privacy_consent')->default(false)->after('acknowledged_accuracy');
            $table->timestamp('certified_at')->nullable()->after('data_privacy_consent');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn(['certified_by', 'acknowledged_accuracy', 'data_privacy_consent', 'certified_at']);
        });
    }
};
