<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Http\Middleware\KeepSignedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
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

    public function test_a_fresh_login_is_remembered_for_a_month(): void
    {
        $this->assertSame(KeepSignedIn::BROWSER_MINUTES, config('auth.guards.web.remember'));
    }
}
