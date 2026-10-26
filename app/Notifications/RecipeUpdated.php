<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Pushmelding "origineel recept aangepast" naar wie een kopie heeft opgeslagen. Tikken opent de kopie. */
class RecipeUpdated extends Notification
{
    public function __construct(public string $title, public string $url, public int $copyId) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Takkie — recept aangepast')
            ->body("„{$this->title}” is aangepast. Wil je de nieuwe versie overnemen?")
            ->icon('/icon-192.png')
            // per kopie één melding: een nieuwe wijziging vervangt de vorige
            ->tag('takkie-recipe-'.$this->copyId)
            ->data(['url' => $this->url])
            ->options(['TTL' => 86400, 'urgency' => 'low']);
    }
}
