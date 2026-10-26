<?php

namespace App\Http\Middleware;

use App\Support\CurrentHousehold;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zorgt dat er een huidig huishouden is en onthoudt de keuze op de gebruiker.
 * Zonder huishouden: naar /onboarding (zoals vinkie's _app-guard).
 */
class EnsureHasHousehold
{
    public function __construct(private CurrentHousehold $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $household = $this->current->get();

        if ($household === null) {
            // toast van de vorige stap (bijv. "Account aangemaakt!") meenemen naar onboarding
            $request->session()->reflash();

            return redirect()->route('onboarding');
        }

        $user = $request->user();

        if ($user->current_household_id !== $household->id) {
            $user->forceFill(['current_household_id' => $household->id])->save();
        }

        return $next($request);
    }
}
