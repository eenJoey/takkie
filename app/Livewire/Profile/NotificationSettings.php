<?php

namespace App\Livewire\Profile;

use App\Models\NotificationSetting;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Meldingen (vinkie lovable NotificationSettings): aan/uit, tot 5 meldingsmomenten,
 * "Alleen taken die aan mij zijn toegewezen", en per apparaat uitzetten.
 * Het aanmelden van het apparaat zelf (toestemming, service worker, abonnement) gebeurt in de browser.
 */
class NotificationSettings extends Component
{
    public bool $enabled = false;

    /** @var list<string> */
    public array $times = ['08:00'];

    public bool $onlyAssigned = false;

    public function mount(): void
    {
        $settings = auth()->user()->notificationSettings;

        if ($settings) {
            $this->enabled = $settings->enabled;
            $this->times = $settings->times ?: ['08:00'];
            $this->onlyAssigned = $settings->only_assigned;
        }
    }

    /**
     * Aanzetten: de browser heeft al toestemming gegeven en een abonnement gemaakt.
     *
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}}  $subscription
     */
    public function enable(array $subscription, string $contentEncoding, string $timezone): void
    {
        $endpoint = (string) ($subscription['endpoint'] ?? '');

        if (! str_starts_with($endpoint, 'https://') || strlen($endpoint) > 500) {
            $this->dispatch('toast', type: 'error', message: 'Meldingen konden niet worden aangezet');

            return;
        }

        auth()->user()->updatePushSubscription(
            $endpoint,
            $subscription['keys']['p256dh'] ?? null,
            $subscription['keys']['auth'] ?? null,
            in_array($contentEncoding, ['aesgcm', 'aes128gcm'], true) ? $contentEncoding : 'aes128gcm',
        );

        $this->enabled = true;
        $this->persist($timezone);
        $this->dispatch('toast', type: 'success', message: 'Meldingen staan aan');
    }

    public function disable(string $timezone): void
    {
        $this->enabled = false;
        $this->persist($timezone);
        $this->dispatch('toast', type: 'success', message: 'Meldingen staan uit');
    }

    public function addTime(): void
    {
        if (count($this->times) < NotificationSetting::MAX_TIMES) {
            $this->times[] = '18:00';
        }
    }

    public function removeTime(int $index): void
    {
        if (count($this->times) > 1) {
            unset($this->times[$index]);
            $this->times = array_values($this->times);
        }
    }

    public function save(string $timezone): void
    {
        $this->persist($timezone);
        $this->dispatch('toast', type: 'success', message: 'Meldingsinstellingen opgeslagen');
    }

    /** Alleen dit apparaat afmelden; de instellingen blijven staan. */
    public function disableDevice(string $endpoint): void
    {
        auth()->user()->deletePushSubscription($endpoint);
        $this->dispatch('toast', type: 'success', message: 'Dit apparaat ontvangt geen meldingen meer');
    }

    private function persist(string $timezone): void
    {
        $this->validate([
            'times' => ['required', 'array', 'min:1', 'max:'.NotificationSetting::MAX_TIMES],
            'times.*' => ['required', 'date_format:H:i'],
        ], ['times.*.date_format' => 'Vul een geldige tijd in (uu:mm)']);

        auth()->user()->notificationSettings()->updateOrCreate([], [
            'enabled' => $this->enabled,
            'times' => array_values(array_unique($this->times)),
            'only_assigned' => $this->onlyAssigned,
            'timezone' => in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Europe/Amsterdam',
        ]);
    }

    public function render(): View
    {
        return view('livewire.profile.notification-settings', [
            'vapidPublicKey' => (string) config('webpush.vapid.public_key'),
            'max' => NotificationSetting::MAX_TIMES,
        ]);
    }
}
