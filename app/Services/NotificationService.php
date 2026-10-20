<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NotificationService
{
    public function fire(string $type, array $payload, int $targetUserId): void
    {
        $notification = Notification::create([
            'user_id' => $targetUserId,
            'type' => $type,
            'title' => $payload['title'] ?? ucfirst(str_replace('_', ' ', $type)),
            'message' => $payload['message'] ?? '',
            'data' => $payload['data'] ?? null,
        ]);

        try {
            Redis::publish('notifications', json_encode([
                'id' => $notification->id,
                'type' => $type,
                'targetUserId' => $targetUserId,
                'title' => $notification->title,
                'message' => $notification->message,
                'created_at' => $notification->created_at?->toISOString(),
            ]));
        } catch (\Throwable $e) {
            Log::warning('Redis notification publish failed', ['error' => $e->getMessage()]);
        }
    }
}
