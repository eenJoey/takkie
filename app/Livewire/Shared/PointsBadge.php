<?php

namespace App\Livewire\Shared;

use App\Support\CurrentHousehold;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Puntensaldo van de ingelogde gebruiker in het huidige huishouden (vinkie PointsBadge, rechtsboven). */
class PointsBadge extends Component
{
    #[On('points-updated')]
    public function refreshPoints(): void
    {
        // render() haalt het saldo opnieuw op
    }

    public function render(): View
    {
        $points = (int) DB::table('household_user')
            ->where('household_id', app(CurrentHousehold::class)->id())
            ->where('user_id', auth()->id())
            ->value('points_balance');

        return view('livewire.shared.points-badge', ['points' => $points]);
    }
}
