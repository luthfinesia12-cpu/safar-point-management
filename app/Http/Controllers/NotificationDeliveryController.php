<?php

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\JsonResponse;

class NotificationDeliveryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(NotificationDelivery::with('recipient')->latest()->paginate(50));
    }

    public function resend(NotificationDelivery $notificationDelivery, NotificationDeliveryService $service): JsonResponse
    {
        abort_unless($notificationDelivery->status === 'failed', 422, 'Hanya notifikasi gagal yang dapat dikirim ulang.');

        return response()->json($service->resend($notificationDelivery));
    }
}
