<?php

namespace App\Jobs;

use App\Services\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWorkflowNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $recipientIds, public string $title, public string $message, public array $context = []) {}

    public function handle(NotificationDeliveryService $service): void
    {
        $service->send($this->recipientIds, $this->title, $this->message, $this->context);
    }
}
