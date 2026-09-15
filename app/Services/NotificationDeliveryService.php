<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Throwable;

class NotificationDeliveryService
{
    public function send(array $recipientIds, string $title, string $message, array $context = []): void
    {
        User::whereIn('id', $recipientIds)->where('is_active', true)->get()->each(function (User $user) use ($title, $message, $context): void {
            $delivery = NotificationDelivery::create(['recipient_id' => $user->id, 'title' => $title, 'message' => $message, 'context' => $context, 'status' => 'pending']);
            $this->deliver($delivery);
        });
    }

    public function resend(NotificationDelivery $delivery): NotificationDelivery
    {
        $retry = NotificationDelivery::create(['recipient_id' => $delivery->recipient_id, 'title' => $delivery->title, 'message' => $delivery->message, 'context' => $delivery->context, 'status' => 'pending', 'resent_from_id' => $delivery->id]);
        $this->deliver($retry);

        return $retry->fresh();
    }

    private function deliver(NotificationDelivery $delivery): void
    {
        try {
            $delivery->increment('attempts');
            $delivery->recipient->notify(new WorkflowNotification($delivery->title, $delivery->message, $delivery->context ?? []));
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'failure_message' => null]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'failed_at' => now(), 'failure_message' => $exception->getMessage()]);
        }
    }
}
