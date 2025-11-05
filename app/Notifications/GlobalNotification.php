<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GlobalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;
    public string $body;

    public function __construct(string $title, string $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return ['database']; // pode adicionar 'mail', 'broadcast'
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
        ];
    }
}
