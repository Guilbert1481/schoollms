<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance-manager email configuration: an SMTP account for sending invoices
 * and Statements of Account (separate from the school-wide system SMTP), an
 * IMAP account for a future finance inbox, and the auto-send switch.
 *
 * auto_send_invoices defaults to FALSE so no school starts emailing guardians
 * until finance explicitly configures and enables it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('last_soa_run_at');
            $table->unsignedInteger('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption', 16)->nullable()->after('smtp_password'); // tls|ssl
            $table->string('mail_from_address')->nullable()->after('smtp_encryption');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');
            $table->string('imap_host')->nullable()->after('mail_from_name');
            $table->unsignedInteger('imap_port')->nullable()->after('imap_host');
            $table->string('imap_username')->nullable()->after('imap_port');
            $table->text('imap_password')->nullable()->after('imap_username');
            $table->string('imap_encryption', 16)->nullable()->after('imap_password'); // ssl|tls
            $table->boolean('auto_send_invoices')->default(false)->after('imap_encryption');
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
                'mail_from_address', 'mail_from_name',
                'imap_host', 'imap_port', 'imap_username', 'imap_password', 'imap_encryption',
                'auto_send_invoices',
            ]);
        });
    }
};
