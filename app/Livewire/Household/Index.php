<?php

namespace App\Livewire\Household;

use App\Actions\CreateHousehold;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Support\CurrentHousehold;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/** /household: leden, uitnodigingen (alleen eigenaar), verlaten (alleen leden) en een nieuw huishouden, zoals vinkie. */
#[Title('Huishouden')]
class Index extends Component
{
    public string $inviteEmail = '';

    public string $newHouseholdName = '';

    #[Computed]
    public function household(): Household
    {
        return app(CurrentHousehold::class)->get();
    }

    #[Computed]
    public function isOwner(): bool
    {
        return auth()->user()->isOwnerOf($this->household);
    }

    #[Computed]
    public function members(): Collection
    {
        return $this->household->users()->orderBy('household_user.joined_at')->get();
    }

    #[Computed]
    public function invites(): Collection
    {
        return $this->isOwner ? HouseholdInvite::open()->latest()->get() : collect();
    }

    public function createInvite(): void
    {
        $this->authorize('manageInvites', $this->household);

        HouseholdInvite::create(['created_by' => auth()->id()]);

        unset($this->invites);
        $this->dispatch('toast', type: 'success', message: 'Uitnodiging aangemaakt');
    }

    /** vinkie bewaarde het e-mailadres alleen; er gaat geen mail uit. */
    public function inviteByEmail(): void
    {
        $this->authorize('manageInvites', $this->household);
        $this->validate(['inviteEmail' => ['required', 'email', 'max:255']]);

        HouseholdInvite::create(['created_by' => auth()->id(), 'email' => $this->inviteEmail]);

        $this->reset('inviteEmail');
        unset($this->invites);
        $this->dispatch('toast', type: 'success', message: 'Uitnodiging aangemaakt');
    }

    public function leave(): void
    {
        $household = $this->household;
        $this->authorize('leave', $household);

        $user = auth()->user();
        $household->users()->detach($user->id);
        $user->forceFill(['current_household_id' => null])->save();
        app(CurrentHousehold::class)->forget();

        session()->flash('toast', ['type' => 'success', 'message' => 'Huishouden verlaten']);
        $this->redirect('/');
    }

    public function createHousehold(CreateHousehold $createHousehold): void
    {
        $this->validate(['newHouseholdName' => ['required', 'string', 'max:255']]);

        $createHousehold->handle(auth()->user(), $this->newHouseholdName);

        session()->flash('toast', ['type' => 'success', 'message' => 'Huishouden aangemaakt']);
        $this->redirectRoute('household');
    }

    public function render(): View
    {
        return view('livewire.household.index');
    }
}
