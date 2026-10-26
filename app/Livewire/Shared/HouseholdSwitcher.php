<?php

namespace App\Livewire\Shared;

use App\Support\CurrentHousehold;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Wisselen tussen huishoudens (vinkie HouseholdSwitcher). De keuze wordt op de gebruiker onthouden. */
class HouseholdSwitcher extends Component
{
    #[Locked]
    public string $returnUrl = '/';

    public function mount(): void
    {
        // op een lijst van het oude huishouden terugkomen zou een 404 geven: dan naar home
        $this->returnUrl = request()->routeIs('lists.show') ? route('lists.index') : url()->current();
    }

    #[Computed]
    public function households(): Collection
    {
        return auth()->user()->households()->orderBy('household_user.joined_at')->get();
    }

    public function switchTo(int $householdId): void
    {
        $household = $this->households->firstWhere('id', $householdId);
        abort_unless($household, 404);

        auth()->user()->forceFill(['current_household_id' => $household->id])->save();
        app(CurrentHousehold::class)->forget();

        $this->redirect($this->returnUrl);
    }

    public function render(): View
    {
        return view('livewire.shared.household-switcher', [
            'current' => app(CurrentHousehold::class)->get(),
        ]);
    }
}
