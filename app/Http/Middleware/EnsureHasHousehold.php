<?php

namespace App\Http\Middleware;

use App\Support\CurrentHousehold;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zorgt dat er een huidig huishouden is en onthoudt de keuze op de gebruiker.
 * M3: zonder huishouden volgt een redirect naar /onboarding; tot die tijd 403.
 */
class EnsureHasHousehold
{
    public function __construct(private CurrentHousehold $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $household = $this->current->get();

        abort_if($household === null, 403, 'Je bent nog geen lid van een huishouden.');

        $user = $request->user();

        if ($user->current_household_id !== $household->id) {
            $user->forceFill(['current_household_id' => $household->id])->save();
        }

        return $next($request);
    }
}
