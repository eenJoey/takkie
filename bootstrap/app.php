<?php

use App\Http\Middleware\EnsureHasHousehold;
use App\Http\Middleware\KeepSignedIn;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'household' => EnsureHasHousehold::class,
        ]);

        // eerst het huishouden bepalen, dan pas route-model-binding (die is huishoud-gescoped)
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: EnsureHasHousehold::class,
        );

        $middleware->redirectUsersTo('/');

        // achter de reverse proxy van Coolify (Traefik) draait HTTPS: X-Forwarded-* vertrouwen, anders worden links http://
        $middleware->trustProxies(at: '*');

        // ingelogd blijven (PWA bijna altijd, browser tot een maand niet gebruikt); het PWA-cookie zet de browser zelf
        $middleware->web(append: [KeepSignedIn::class]);
        $middleware->encryptCookies(except: [KeepSignedIn::PWA_COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
