<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class DynamicPushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;

    public function __construct($title, $body, $data = [])
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
{
    return FcmMessage::create()
        ->notification(new FcmNotification(
            title: $this->title,
            body: $this->body,
        ));
}
}
