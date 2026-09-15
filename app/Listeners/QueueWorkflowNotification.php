<?php

namespace App\Listeners;

use App\Events\WorkflowStatusChanged;
use App\Jobs\SendWorkflowNotification;

class QueueWorkflowNotification
{
    public function handle(WorkflowStatusChanged $event): void
    {
        SendWorkflowNotification::dispatch($event->recipientIds, $event->title, $event->message, $event->context)->afterCommit();
    }
}
