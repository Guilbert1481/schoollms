<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TermCreatedNotification extends Notification
{
    use Queueable;

    protected $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'New Term Created',
            'message' => $this->term->name . ' is now open for enrollment setup.',
            'term_id' => $this->term->id,
        ];
    }
}