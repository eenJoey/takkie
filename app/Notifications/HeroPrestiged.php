<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Pushmelding naar het huishouden als iemand prestige doet. Tikken opent "Mijn held". */
class HeroPrestiged extends Notification
{
    public function __construct(public string $name, public string $title) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Takkie — prestige! ⭐')
            ->body("{$this->name} is nu {$this->title}!")
            ->icon('/icon-192.png')
            ->tag('takkie-hero')
            ->data(['url' => route('hero', absolute: false)])
            ->options(['TTL' => 86400, 'urgency' => 'low']);
    }
}
