<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Http\Middleware\KeepSignedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/** Niet telkens opnieuw inloggen: het onthoud-cookie schuift op (PWA 400 dagen, browser 30 dagen). */
class KeepSignedInTest extends TestCase
{
    use RefreshDatabase;

    private function signedInViaRememberCookie(bool $pwa = false): TestResponse
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        app(CreateHousehold::class)->handle($user, 'Thuis');

        $guard = Auth::guard('web');
        $recaller = $user->id.'|'.$user->remember_token.'|'.$guard->hashPasswordForCookie($user->password);

        $request = $this->withCookie($guard->getRecallerName(), $recaller);

        if ($pwa) {
            $request->withUnencryptedCookie(KeepSignedIn::PWA_COOKIE, '1');
        }

        $response = $request->get(route('lists.index'))->assertOk();
        $this->assertAuthenticatedAs($user);

        return $response;
    }

    private function rememberMinutesLeft(TestResponse $response): float
    {
        $cookie = $response->getCookie(Auth::guard('web')->getRecallerName(), false);
        $this->assertNotNull($cookie);

        return ($cookie->getExpiresTime() - time()) / 60;
    }

    public function test_the_browser_stays_signed_in_for_a_month_after_the_last_visit(): void
    {
        $minutes = $this->rememberMinutesLeft($this->signedInViaRememberCookie());

        $this->assertEqualsWithDelta(KeepSignedIn::BROWSER_MINUTES, $minutes, 2);
    }

    public function test_the_home_screen_app_stays_signed_in_much_longer(): void
    {
        $minutes = $this->rememberMinutesLeft($this->signedInViaRememberCookie(pwa: true));

        $this->assertEqualsWithDelta(KeepSignedIn::PWA_MINUTES, $minutes, 2);
    }

    public function test_guests_and_logged_out_users_get_no_remember_cookie(): void
    {
        $this->get('/login')->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    private function recallerName(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    /** Doet alsof de app later opnieuw opent: sessie weg, alleen het onthoud-cookie (en evt. het PWA-cookie) blijft. */
    private function reopenWith(string $recaller, bool $pwa = false): TestResponse
    {
        Auth::forgetGuards();
        $this->flushSession();

        $request = $this->withCookie($this->recallerName(), $recaller);

        if ($pwa) {
            $request->withUnencryptedCookie(KeepSignedIn::PWA_COOKIE, '1');
        }

        return $request->get(route('lists.index'));
    }

    public function test_after_a_google_login_in_the_app_you_stay_signed_in_when_reopening(): void
    {
        $googleUser = (new SocialiteUser)->map(['id' => 'g-1', 'email' => 'joey@voorbeeld.nl', 'name' => 'Joey', 'avatar' => null]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // verouderd cookie van een eerdere login meesturen: mag het nieuwe cookie niet overschrijven
        $response = $this->withUnencryptedCookie(KeepSignedIn::PWA_COOKIE, '1')
            ->withCookie($this->recallerName(), '1|verlopen-token|onzin')
            ->get(route('auth.google.callback'));

        $user = User::sole();
        app(CreateHousehold::class)->handle($user, 'Thuis');
        $recaller = $response->getCookie($this->recallerName())->getValue();

        $this->assertStringStartsWith($user->id.'|'.$user->fresh()->remember_token.'|', $recaller);

        $this->reopenWith($recaller, pwa: true)->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_accounts_still_cannot_log_in_with_a_password(): void
    {
        User::factory()->create(['email' => 'g@voorbeeld.nl', 'password' => null]);

        $this->assertFalse(Auth::attempt(['email' => 'g@voorbeeld.nl', 'password' => 'x']));
        $this->assertFalse(Auth::attempt(['email' => 'g@voorbeeld.nl', 'password' => '']));
    }

    public function test_a_stale_cookie_is_replaced_by_a_valid_one_while_signed_in(): void
    {
        $user = User::factory()->create(['remember_token' => null]);
        app(CreateHousehold::class)->handle($user, 'Thuis');

        $response = $this->actingAs($user)
            ->withCookie($this->recallerName(), $user->id.'|oud-token|oud')
            ->get(route('lists.index'))
            ->assertOk();

        $recaller = $response->getCookie($this->recallerName())->getValue();
        $this->assertNotNull($user->fresh()->remember_token);
        $this->assertStringContainsString('|'.$user->fresh()->remember_token.'|', $recaller);

        $this->reopenWith($recaller)->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_fresh_login_is_remembered_for_a_month(): void
    {
        $this->assertSame(KeepSignedIn::BROWSER_MINUTES, config('auth.guards.web.remember'));
    }
}
