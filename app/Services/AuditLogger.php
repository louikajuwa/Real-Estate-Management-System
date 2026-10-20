<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    private const NOTIFICATION_TRIGGERS = [
        'inquiry.submitted' => 'inquiry_submitted',
        'booking.requested' => 'booking_requested',
        'inquiry.status_changed' => 'inquiry_status_changed',
        'property.status_changed' => 'property_status_changed',
    ];

    public function __construct(private NotificationService $notificationService) {}

    public function log(
        string $action,
        mixed $subject = null,
        mixed $old = null,
        mixed $new = null,
        string $description = '',
        ?int $targetUserId = null
    ): AuditLog {
        $request = app(Request::class);
        $log = AuditLog::create([
            'agency_id' => Auth::user()?->agency_id ?: data_get($subject, 'agency_id'),
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'old_values' => $old ? (is_array($old) ? $old : $old->toArray()) : null,
            'new_values' => $new ? (is_array($new) ? $new : $new->toArray()) : null,
            'description' => $description ?: $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Fire notification if this action is a trigger type
        if (isset(self::NOTIFICATION_TRIGGERS[$action]) && $targetUserId) {
            try {
                $this->notificationService->fire(
                    self::NOTIFICATION_TRIGGERS[$action],
                    ['title' => $description, 'message' => $description, 'data' => ['log_id' => $log->id]],
                    $targetUserId
                );
            } catch (\Throwable $e) {
                // Notification failure never blocks the audit log
            }
        }

        return $log;
    }
}
