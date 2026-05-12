<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
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
        'smtp_host',
        'sms_api'
    ];
}