<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Pushmelding "tijd voor taken" (vinkie send-reminders: titel, tekst, TTL 1 uur, urgentie normaal). */
class TaskReminder extends Notification
{
    public function __construct(public string $body) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Takkie — tijd voor taken ✓')
            ->body($this->body)
            ->icon('/icon-192.png')
            ->tag('takkie-reminder')
            ->data(['url' => '/dashboard'])
            ->options(['TTL' => 3600, 'urgency' => 'normal']);
    }
}
