<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use Auditable; // admin-action log (Phase 4)

    /** Secrets stay out of the audit log — only the fact of a change shows. */
    protected array $auditExclude = ['smtp_password', 'sms_api_key', 'sms_api_secret'];

    protected $fillable = [
        'school_id',
        'session_timeout',
        'pagination',
        'timezone',
        'date_format',
        'maintenance_mode',
        'upload_limit',
        'allowed_file_types',
        'backup_schedule',
        'school_name',
        'system_logo',
        // Mail / SMTP
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        // SMS gateway
        'sms_api',
        'sms_provider',
        'sms_api_key',
        'sms_api_secret',
        'sms_sender_name',
        'sms_from_number',
        'sms_enabled',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'sms_enabled' => 'boolean',
        'smtp_port' => 'integer',
    ];

    protected $hidden = [
        'smtp_password',
        'sms_api_key',
        'sms_api_secret',
    ];
}
