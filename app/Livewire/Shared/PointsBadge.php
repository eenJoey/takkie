<?php

namespace App\Livewire\Shared;

use App\Support\CurrentHousehold;
use App\Support\Streak;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Puntensaldo en streak (🔥 dagen op rij) van de ingelogde gebruiker in het huidige huishouden (vinkie PointsBadge, rechtsboven). */
class PointsBadge extends Component
{
    #[On('points-updated')]
    public function refreshPoints(): void
    {
        // render() haalt het saldo opnieuw op
    }

    public function render(): View
    {
        $householdId = app(CurrentHousehold::class)->id();
        $points = (int) DB::table('household_user')
            ->where('household_id', $householdId)
            ->where('user_id', auth()->id())
            ->value('points_balance');

        return view('livewire.shared.points-badge', [
            'points' => $points,
            'streak' => $householdId ? Streak::for(auth()->user(), $householdId) : 0,
        ]);
    }
}
