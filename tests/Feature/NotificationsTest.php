<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Enums\ListType;
use App\Enums\RecurrenceType;
use App\Livewire\Profile\NotificationSettings;
use App\Models\Household;
use App\Models\NotificationSetting;
use App\Models\TodoList;
use App\Models\User;
use App\Notifications\TaskReminder;
use App\Support\CurrentHousehold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

/** PWA en herinneringen (vinkie lovable, M9). */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    private array $subscription = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        'keys' => ['p256dh' => 'BPublicKeyVoorbeeld', 'auth' => 'AuthVoorbeeld'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // dinsdag 27 oktober 2026, 08:05 in Amsterdam
        Carbon::setTestNow(Carbon::parse('2026-10-27 08:05', 'Europe/Amsterdam'));

        $this->user = User::factory()->create();
        $this->household = app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
    }

    private function task(array $attributes = []): void
    {
        app(CurrentHousehold::class)->runAs($this->household, fn () => TodoList::where('type', ListType::Tasks)->sole()->tasks()->create([
            'title' => 'Afwassen',
            'points' => 10,
            'recurrence_type' => RecurrenceType::Daily,
            'due_date' => '2026-10-27',
            ...$attributes,
        ]));
    }

    private function enableFor(User $user, array $settings = []): void
    {
        $user->updatePushSubscription($this->subscription['endpoint'].'-'.$user->id, 'key', 'token', 'aes128gcm');
        $user->notificationSettings()->create(['times' => ['08:00'], 'timezone' => 'Europe/Amsterdam', ...$settings]);
    }

    public function test_pwa_manifest_icons_and_service_worker_are_served(): void
    {
        $this->assertFileExists(public_path('manifest.json'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icon-192.png'));
        $this->assertFileExists(public_path('icon-512.png'));

        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertSame('Takkie', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);

        $this->get(route('lists.index'))->assertSee('rel="manifest"', false)->assertSee('apple-mobile-web-app-capable', false);
    }

    public function test_profile_shows_notification_card(): void
    {
        $this->get('/profiel')->assertSee('Meldingen')->assertSee('Ontvang een melding op de dagen dat er taken openstaan.');
    }

    public function test_enabling_stores_subscription_and_settings(): void
    {
        Livewire::test(NotificationSettings::class)
            ->call('enable', $this->subscription, 'aes128gcm', 'Europe/Amsterdam')
            ->assertSet('enabled', true)
            ->assertDispatched('toast', type: 'success', message: 'Meldingen staan aan')
            ->assertSee('Meldingsmomenten (1/5)');

        $this->assertSame(1, $this->user->pushSubscriptions()->count());
        $settings = $this->user->notificationSettings()->first();
        $this->assertTrue($settings->enabled);
        $this->assertSame(['08:00'], $settings->times);
        $this->assertSame('Europe/Amsterdam', $settings->timezone);
    }

    public function test_invalid_subscription_is_refused(): void
    {
        Livewire::test(NotificationSettings::class)
            ->call('enable', ['endpoint' => 'http://evil.example/x', 'keys' => []], 'aes128gcm', 'Europe/Amsterdam')
            ->assertDispatched('toast', type: 'error');

        $this->assertSame(0, $this->user->pushSubscriptions()->count());
    }

    public function test_times_can_be_added_up_to_five_and_saved(): void
    {
        $component = Livewire::test(NotificationSettings::class)->call('enable', $this->subscription, 'aes128gcm', 'Europe/Amsterdam');

        for ($i = 0; $i < 6; $i++) {
            $component->call('addTime');
        }

        $component->assertCount('times', 5)
            ->set('times.1', '07:30')
            ->call('removeTime', 4)
            ->set('onlyAssigned', true)
            ->call('save', 'Europe/Amsterdam')
            ->assertDispatched('toast', type: 'success', message: 'Meldingsinstellingen opgeslagen');

        $settings = $this->user->notificationSettings()->first();
        $this->assertSame(['08:00', '07:30', '18:00'], $settings->times);
        $this->assertTrue($settings->only_assigned);

        $component->set('times.0', 'later')->call('save', 'Europe/Amsterdam')->assertHasErrors(['times.0']);
    }

    public function test_device_can_be_turned_off(): void
    {
        Livewire::test(NotificationSettings::class)
            ->call('enable', $this->subscription, 'aes128gcm', 'Europe/Amsterdam')
            ->call('disableDevice', $this->subscription['endpoint'])
            ->assertDispatched('toast', type: 'success', message: 'Dit apparaat ontvangt geen meldingen meer');

        $this->assertSame(0, $this->user->pushSubscriptions()->count());
        $this->assertTrue($this->user->notificationSettings()->first()->enabled);
    }

    public function test_slot_matching_uses_the_users_timezone(): void
    {
        $settings = new NotificationSetting(['times' => ['08:00', '18:00'], 'timezone' => 'Europe/Amsterdam']);

        $this->assertTrue($settings->matchesSlot(Carbon::parse('2026-10-27 08:00', 'Europe/Amsterdam')));
        $this->assertTrue($settings->matchesSlot(Carbon::parse('2026-10-27 08:14', 'Europe/Amsterdam')));
        $this->assertFalse($settings->matchesSlot(Carbon::parse('2026-10-27 08:15', 'Europe/Amsterdam')));
        $this->assertFalse($settings->matchesSlot(Carbon::parse('2026-10-27 07:59', 'Europe/Amsterdam')));
        $this->assertTrue($settings->matchesSlot(Carbon::parse('2026-10-27 17:05', 'UTC')));
    }

    public function test_reminder_is_sent_for_open_tasks_at_a_reminder_time(): void
    {
        Notification::fake();
        $this->enableFor($this->user);
        $this->task(['title' => 'Afwassen']);
        $this->task(['title' => 'Stofzuigen', 'due_date' => '2026-10-20']);
        $this->task(['title' => 'Morgen pas', 'due_date' => '2026-10-28']);

        $this->artisan('reminders:send')->assertSuccessful();

        Notification::assertSentTo($this->user, TaskReminder::class, function (TaskReminder $notification, array $channels) {
            return $channels === [WebPushChannel::class]
                && $notification->body === '2 taken staan open, o.a. Stofzuigen';
        });
    }

    public function test_single_task_uses_its_title_and_payload_matches_vinkie(): void
    {
        Notification::fake();
        $this->enableFor($this->user);
        $this->task(['title' => 'Afwassen']);

        $this->artisan('reminders:send');

        Notification::assertSentTo($this->user, TaskReminder::class, function (TaskReminder $notification) {
            $message = $notification->toWebPush($this->user, $notification)->toArray();

            return $notification->body === 'Afwassen'
                && $message['title'] === 'Takkie — tijd voor taken ✓'
                && $message['data'] === ['url' => '/dashboard'];
        });
    }

    public function test_no_reminder_outside_the_time_window_or_without_device(): void
    {
        Notification::fake();
        $this->task();

        $this->user->notificationSettings()->create(['times' => ['08:00']]);
        $this->artisan('reminders:send');
        Notification::assertNothingSent();

        $this->user->updatePushSubscription('https://push.example/endpoint', 'key', 'token');
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00', 'Europe/Amsterdam'));
        $this->artisan('reminders:send');
        Notification::assertNothingSent();
    }

    public function test_disabled_settings_completed_tasks_and_checklists_are_ignored(): void
    {
        Notification::fake();
        $this->enableFor($this->user, ['enabled' => false]);
        $this->task();
        $this->artisan('reminders:send');
        Notification::assertNothingSent();

        $this->user->notificationSettings()->update(['enabled' => true]);
        TodoList::withoutGlobalScopes()->where('type', ListType::Tasks)->first()->tasks()->withoutGlobalScopes()->update(['completed_at' => now()]);
        app(CurrentHousehold::class)->runAs($this->household, function () {
            $checklist = TodoList::create(['name' => 'Boodschappen']);
            $checklist->tasks()->create(['title' => 'Melk', 'due_date' => '2026-10-27']);
        });

        $this->artisan('reminders:send');
        Notification::assertNothingSent();
    }

    public function test_only_assigned_skips_tasks_of_others_but_keeps_everyone_tasks(): void
    {
        Notification::fake();
        $sanne = User::factory()->create();
        $this->household->addMember($sanne);
        $this->enableFor($this->user, ['only_assigned' => true]);
        $this->task(['title' => 'Voor Sanne', 'assigned_to' => $sanne->id]);
        $this->task(['title' => 'Voor iedereen']);

        $this->artisan('reminders:send');

        Notification::assertSentTo($this->user, TaskReminder::class, fn ($n) => $n->body === 'Voor iedereen');
    }

    public function test_reminders_are_scheduled_every_fifteen_minutes(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('reminders:send');
    }
}
