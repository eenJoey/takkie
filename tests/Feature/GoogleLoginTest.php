<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/** "Doorgaan met Google" via Socialite (M8). Google zelf wordt nagebootst. */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name = 'Joey', ?string $avatar = 'https://lh3.googleusercontent.com/a/foto'): void
    {
        $googleUser = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => $name, 'avatar' => $avatar]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_page_shows_google_button(): void
    {
        $this->get('/login')->assertSee('Doorgaan met Google')->assertSee(route('auth.google'));
    }

    public function test_redirect_without_configuration_shows_a_message(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('auth.google'))->assertRedirect('/login')->assertSessionHas('toast.message', 'Google login is nog niet ingesteld');
    }

    public function test_redirect_goes_to_google_when_configured(): void
    {
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $response->headers->get('Location'));
    }

    public function test_new_google_user_gets_an_account_and_goes_to_onboarding(): void
    {
        $this->fakeGoogleUser('google-sub-1', 'Nieuw@Voorbeeld.nl', 'Nieuwe Gebruiker');

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $user = User::sole();
        $this->assertSame('nieuw@voorbeeld.nl', $user->email);
        $this->assertSame('google-sub-1', $user->google_id);
        $this->assertSame('Nieuwe Gebruiker', $user->name);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->remember_token);
        $this->assertAuthenticatedAs($user);

        $this->get('/')->assertRedirect('/onboarding');
    }

    public function test_existing_user_is_matched_on_google_id_first(): void
    {
        // zoals gemigreerde vinkie-gebruikers: google_id uit auth.identities, e-mail kan afwijken
        $user = User::factory()->create(['email' => 'oud@voorbeeld.nl', 'google_id' => 'google-sub-2']);
        $this->fakeGoogleUser('google-sub-2', 'nieuw-adres@voorbeeld.nl');

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_existing_password_user_gets_google_linked_by_email(): void
    {
        $user = User::factory()->create(['email' => 'joey@voorbeeld.nl', 'avatar' => null]);
        $this->fakeGoogleUser('google-sub-3', 'joey@voorbeeld.nl');

        $this->get(route('auth.google.callback'));

        $user->refresh();
        $this->assertSame('google-sub-3', $user->google_id);
        $this->assertSame('https://lh3.googleusercontent.com/a/foto', $user->avatar);
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_google_callback_shows_vinkie_message(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \Exception('state mismatch'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/login')
            ->assertSessionHas('toast.message', 'Google login mislukt');
        $this->assertGuest();
    }

    public function test_google_only_user_cannot_log_in_with_empty_password(): void
    {
        User::factory()->create(['email' => 'joey@voorbeeld.nl', 'password' => null, 'google_id' => 'g']);

        Livewire::test(Login::class)
            ->set('email', 'joey@voorbeeld.nl')
            ->set('password', 'iets123')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
