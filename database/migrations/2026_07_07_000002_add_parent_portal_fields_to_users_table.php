<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account-level fields for auto-provisioned parent portal users:
 * - password_change_required: set when an account is created (or re-issued)
 *   with a generated one-time password; a middleware forces the password
 *   screen until the user picks their own.
 * - credentials_sent_at: when the credential email last went out (NULL means
 *   the send failed or never happened — surfaced on the registrar panel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_change_required')->default(false)->after('remember_token');
            $table->timestamp('credentials_sent_at')->nullable()->after('password_change_required');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_change_required', 'credentials_sent_at']);
        });
    }
};
