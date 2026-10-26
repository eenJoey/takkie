<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

/** De inlogflow moet 1-op-1 werken zoals vinkie (MIGRATIE-PLAN.md, sectie b). */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_both_tabs(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Inloggen')
            ->assertSee('Account maken')
            ->assertSee('Wachtwoord vergeten?');
    }

    public function test_sign_up_creates_an_account_and_logs_in_immediately(): void
    {
        Livewire::test(Login::class)
            ->call('setMode', 'signup')
            ->set('email', 'Nieuw@Voorbeeld.nl')
            ->set('password', 'geheim1')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $user = User::where('email', 'nieuw@voorbeeld.nl')->sole();
        $this->assertSame('nieuw', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
        $this->assertSame('Account aangemaakt!', session('toast')['message']);
    }

    public function test_sign_up_uses_the_given_name(): void
    {
        Livewire::test(Login::class)
            ->call('setMode', 'signup')
            ->set('name', 'Joey')
            ->set('email', 'joey@voorbeeld.nl')
            ->set('password', 'geheim1')
            ->call('submit');

        $this->assertSame('Joey', User::sole()->name);
    }

    public function test_sign_up_rejects_short_password_and_existing_email(): void
    {
        User::factory()->create(['email' => 'bestaat@voorbeeld.nl']);

        Livewire::test(Login::class)
            ->call('setMode', 'signup')
            ->set('email', 'kort@voorbeeld.nl')
            ->set('password', '12345')
            ->call('submit')
            ->assertHasErrors(['password' => 'min'])
            ->assertDispatched('toast');

        Livewire::test(Login::class)
            ->call('setMode', 'signup')
            ->set('email', 'bestaat@voorbeeld.nl')
            ->set('password', 'geheim1')
            ->call('submit')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertGuest();
    }

    public function test_user_can_log_in_and_is_remembered(): void
    {
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl', 'password' => 'geheim1']);

        Livewire::test(Login::class)
            ->set('email', 'joey@voorbeeld.nl')
            ->set('password', 'geheim1')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_wrong_password_shows_an_error(): void
    {
        User::factory()->create(['email' => 'joey@voorbeeld.nl', 'password' => 'geheim1']);

        Livewire::test(Login::class)
            ->set('email', 'joey@voorbeeld.nl')
            ->set('password', 'fout123')
            ->call('submit')
            ->assertHasErrors('email')
            ->assertDispatched('toast', type: 'error', message: 'Onjuist e-mailadres of wachtwoord.');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'joey@voorbeeld.nl', 'password' => 'geheim1']);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)->set('email', 'joey@voorbeeld.nl')->set('password', 'fout123')->call('submit');
        }

        Livewire::test(Login::class)
            ->set('email', 'joey@voorbeeld.nl')
            ->set('password', 'geheim1')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_logged_in_user_is_sent_home_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
    }

    public function test_logout_goes_back_to_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_forgot_password_sends_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'joey@voorbeeld.nl')
            ->call('send')
            ->assertSet('sent', true)
            ->assertSee('Check je inbox voor de reset link.');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_does_not_reveal_unknown_addresses(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'onbekend@voorbeeld.nl')
            ->call('send')
            ->assertSet('sent', true);

        Notification::assertNothingSent();
    }

    public function test_reset_link_points_to_the_reset_page(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl']);

        Password::sendResetLink(['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/reset-password/'.$notification->token)
                && str_contains($url, 'email=joey%40voorbeeld.nl');
        });
    }

    public function test_password_can_be_reset_and_user_is_logged_in(): void
    {
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl']);
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email=joey@voorbeeld.nl')->assertOk()->assertSee('Nieuw wachtwoord');

        Livewire::withQueryParams(['email' => 'joey@voorbeeld.nl'])
            ->test(ResetPassword::class, ['token' => $token])
            ->set('password', 'nieuw123')
            ->call('save')
            ->assertRedirect('/');

        $this->assertTrue(Hash::check('nieuw123', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Wachtwoord aangepast!', session('toast')['message']);
    }

    public function test_invalid_reset_token_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl']);

        Livewire::withQueryParams(['email' => 'joey@voorbeeld.nl'])
            ->test(ResetPassword::class, ['token' => 'ongeldig'])
            ->set('password', 'nieuw123')
            ->call('save')
            ->assertNoRedirect()
            ->assertDispatched('toast', type: 'error');

        $this->assertFalse(Hash::check('nieuw123', $user->fresh()->password));
        $this->assertGuest();
    }
}
