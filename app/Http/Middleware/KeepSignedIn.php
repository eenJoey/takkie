<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ingelogd blijven: het "onthoud mij"-cookie schuift bij elk bezoek op.
 * In de app op het beginscherm (PWA) zo goed als nooit uitloggen, in de browser pas na een maand niet gebruiken.
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

        $guard = Auth::guard('web');
        $recaller = $request->cookies->get($guard->getRecallerName());

        // alleen bij gewone paginaloads, niet bij elk Livewire-verzoek
        if ($request->isMethod('GET') && is_string($recaller) && $recaller !== '' && $guard->check()) {
            Cookie::queue($guard->getRecallerName(), $recaller, $request->cookies->has(self::PWA_COOKIE) ? self::PWA_MINUTES : self::BROWSER_MINUTES);
        }

        return $response;
    }
}
