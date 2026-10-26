<?php

namespace App\Livewire;

use App\Actions\AcceptInvite;
use App\Exceptions\InviteException;
use App\Models\HouseholdInvite;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /invite/{code}: ook uitgelogd te bekijken, zoals vinkie. Toont alleen de huishoudnaam.
 * Uitgelogd: "Eerst inloggen" (de code gaat daarbij verloren, 1-op-1 vinkie; zie Q7).
 */
#[Layout('layouts::guest')]
#[Title('Uitnodiging')]
class InviteShow extends Component
{
    #[Locked]
    public string $code = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    public function accept(AcceptInvite $acceptInvite): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        try {
            $acceptInvite->handle(auth()->user(), $this->code);
        } catch (InviteException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        session()->flash('toast', ['type' => 'success', 'message' => 'Je bent toegevoegd!']);
        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.invite-show', [
            'invite' => HouseholdInvite::findOpenByCode($this->code)?->load('household'),
        ]);
    }
}
