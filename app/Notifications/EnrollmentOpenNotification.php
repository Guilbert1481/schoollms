<?php

namespace App\Notifications;

use App\Models\EnrollmentSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentOpenNotification extends Notification
{
    use Queueable;

    public function __construct(public EnrollmentSetting $setting)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $window = trim(
            ($this->setting->start_date ? \Carbon\Carbon::parse($this->setting->start_date)->format('M d') : '')
            .' – '
            .($this->setting->end_date ? \Carbon\Carbon::parse($this->setting->end_date)->format('M d, Y') : '')
        );

        return [
            'title'                 => 'Enrollment is now open',
            'message'               => trim(($this->setting->title ?: $this->setting->name).' '.($window ? "($window)" : '')),
            'type'                  => 'enrollment',
            'reference_id'          => $this->setting->term_id,
            'term_id'               => $this->setting->term_id,
            'enrollment_setting_id' => $this->setting->id,
        ];
    }
}
