<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * TIJDELIJK (M1): echte inlogschermen volgen in M2 (zie MIGRATIE-PLAN.md, sectie b).
 * Alleen in de lokale omgeving: logt de geseede testgebruiker in. Elders: 404.
 */
class TemporaryLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 404);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('lists.index');
    }
}
