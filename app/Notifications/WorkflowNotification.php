<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(private string $title, private string $message, private array $context = []) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'context' => $this->context];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->message);
    }
}
