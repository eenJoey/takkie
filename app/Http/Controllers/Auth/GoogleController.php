<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Doorgaan met Google" (vinkie gebruikte de Lovable-broker; hier Socialite).
 * Koppelt op Google-id (de vaste Google-"sub", ook gemigreerd uit vinkie's auth.identities),
 * anders op e-mailadres, anders een nieuw account. Daarna altijd onthouden, net als gewoon inloggen.
 */
class GoogleController extends Controller
{
    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->failed('Google login is nog niet ingesteld');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->failed();
        }

        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '' || ! $googleUser->getId()) {
            return $this->failed();
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?? $googleUser->getId(),
                'avatar' => $user->avatar ?? $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::forceCreate([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => null,
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }

    private function failed(string $message = 'Google login mislukt'): RedirectResponse
    {
        return redirect()->route('login')->with('toast', ['type' => 'error', 'message' => $message]);
    }
}
