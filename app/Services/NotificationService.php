<?php

namespace App\Services;

use App\Models\Notification;
use App\Notifications\DynamicPushNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;

class NotificationService
{
    public function send(Model $notifiable, string $type, string $title, string $body, array $data = []): Notification
    {
        $record = $notifiable->Notifications()->create([
            'type' => $type, 'title' => $title, 'body' => $body, 'data' => $data,
        ]);

        if (! $notifiable->fcm_token) {
            return $record;
        }

        try {
            $report = app(FcmChannel::class)
                ->send($notifiable, new DynamicPushNotification($title, $body, $data + [
                    'type' => $type,
                    'notification_id' => $record->id,
                ]))
                ->first();

            if ($report && ! $report->hasFailures()) {
                $record->update(['sent_at' => now()]);
            } elseif ($report) {
                // dead token: clear it so we stop sending to it
                $notifiable->update(['fcm_token' => null]);
            }
        } catch (\Throwable $e) {
            Log::warning('FCM send failed', ['id' => $record->id, 'error' => $e->getMessage()]);
        }

        return $record;
    }

public function unreadCount(Model $notifiable): int
{
    return $notifiable->Notifications()->whereNull('read_at')->count();
}

public function markAsRead(Model $notifiable, int $id): void
{
    $notifiable->Notifications()->whereKey($id)->firstOrFail()
        ->update(['read_at' => now()]);
}

public function markAllAsRead(Model $notifiable): void
{
    $notifiable->Notifications()->whereNull('read_at')->update(['read_at' => now()]);
}

public function saveToken(Model $notifiable, string $token): void
{
    // a device token belongs to one account: clear it from any other
    $notifiable::where('fcm_token', $token)
        ->whereKeyNot($notifiable->getKey())
        ->update(['fcm_token' => null]);

    $notifiable->update(['fcm_token' => $token]);
}
}