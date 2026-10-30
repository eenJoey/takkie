<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ingelogd blijven: bij elk bezoek een vers "onthoud mij"-cookie voor wie ingelogd is.
 * In de app op het beginscherm (PWA) zo goed als nooit uitloggen, in de browser pas na een maand niet gebruiken.
 *
 * Het cookie wordt altijd opnieuw opgebouwd uit de gebruiker, nooit het meegestuurde cookie verlengd:
 * een verouderd cookie (bijv. na uitloggen op een ander apparaat) zou anders een vers cookie overschrijven.
 */
class KeepSignedIn
{
    /** Cookie die resources/js/app.js zet als Takkie als app (standalone) draait. */
    public const PWA_COOKIE = 'takkie_pwa';

    public const PWA_MINUTES = 60 * 24 * 400;

    public const BROWSER_MINUTES = 60 * 24 * 30;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        // alleen bij gewone paginaloads, niet bij elk Livewire-verzoek
        if ($request->isMethod('GET') && $guard->check()) {
            Cookie::queue(
                $guard->getRecallerName(),
                $this->recaller($guard, $guard->user()),
                $request->cookies->has(self::PWA_COOKIE) ? self::PWA_MINUTES : self::BROWSER_MINUTES,
            );
        }

        return $response;
    }

    /** Zelfde inhoud als SessionGuard::queueRecallerCookie(): id|remember_token|hash van het wachtwoord. */
    private function recaller(SessionGuard $guard, Authenticatable $user): string
    {
        if (blank($user->getRememberToken())) {
            $guard->getProvider()->updateRememberToken($user, Str::random(60));
        }

        return $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.$guard->hashPasswordForCookie($user->getAuthPassword());
    }
}
