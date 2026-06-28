<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            // SMTP details
            if (! Schema::hasColumn('system_settings', 'smtp_port')) {
                $table->integer('smtp_port')->nullable()->after('smtp_host');
            }
            if (! Schema::hasColumn('system_settings', 'smtp_username')) {
                $table->string('smtp_username')->nullable()->after('smtp_port');
            }
            if (! Schema::hasColumn('system_settings', 'smtp_password')) {
                $table->string('smtp_password', 500)->nullable()->after('smtp_username');
            }
            if (! Schema::hasColumn('system_settings', 'smtp_encryption')) {
                $table->string('smtp_encryption', 16)->nullable()->after('smtp_password'); // tls|ssl|null
            }
            if (! Schema::hasColumn('system_settings', 'smtp_from_address')) {
                $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            }
            if (! Schema::hasColumn('system_settings', 'smtp_from_name')) {
                $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
            }

            // SMS gateway details
            if (! Schema::hasColumn('system_settings', 'sms_provider')) {
                $table->string('sms_provider', 64)->nullable()->after('sms_api'); // semaphore|movider|itexmo|twilio|vonage|custom
            }
            if (! Schema::hasColumn('system_settings', 'sms_api_key')) {
                $table->string('sms_api_key', 500)->nullable()->after('sms_provider');
            }
            if (! Schema::hasColumn('system_settings', 'sms_api_secret')) {
                $table->string('sms_api_secret', 500)->nullable()->after('sms_api_key');
            }
            if (! Schema::hasColumn('system_settings', 'sms_sender_name')) {
                $table->string('sms_sender_name', 64)->nullable()->after('sms_api_secret');
            }
            if (! Schema::hasColumn('system_settings', 'sms_from_number')) {
                $table->string('sms_from_number', 32)->nullable()->after('sms_sender_name');
            }
            if (! Schema::hasColumn('system_settings', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(false)->after('sms_from_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $cols = [
                'smtp_port','smtp_username','smtp_password','smtp_encryption',
                'smtp_from_address','smtp_from_name',
                'sms_provider','sms_api_key','sms_api_secret','sms_sender_name',
                'sms_from_number','sms_enabled',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('system_settings', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
