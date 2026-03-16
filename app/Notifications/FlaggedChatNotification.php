<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FlaggedChatNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database']; // can add 'mail' later
    }

    public function toDatabase($notifiable)
    {
        return [
            'chat_thread_id' => $this->message->chat_thread_id,
            'message_id'     => $this->message->id,
            'sender'         => $this->message->user->name,
            'preview'        => substr($this->message->message, 0, 80),
            'type'           => 'flagged_chat',
        ];
    }
}
