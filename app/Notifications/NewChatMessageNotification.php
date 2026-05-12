<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewChatMessageNotification extends Notification
{
    use Queueable;

    protected ChatMessage $message;
    protected string $senderName;

    public function __construct(ChatMessage $message, string $senderName)
    {
        $this->message = $message;
        $this->senderName = $senderName;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return $this->payload();
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->payload());
    }

    public function broadcastType()
    {
        return 'chat.message';
    }

    protected function payload(): array
    {
        return [
            'type'           => 'chat_message',
            'title'          => 'New message from ' . $this->senderName,
            'message'        => \Illuminate\Support\Str::limit($this->message->message, 80),
            'reference_id'   => $this->message->chat_thread_id,
            'chat_thread_id' => $this->message->chat_thread_id,
            'message_id'     => $this->message->id,
            'sender'         => $this->senderName,
        ];
    }
}
