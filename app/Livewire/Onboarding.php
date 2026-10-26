<?php

namespace App\Livewire;

use App\Actions\AcceptInvite;
use App\Actions\CreateHousehold;
use App\Exceptions\InviteException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** /onboarding: eerste huishouden maken of lid worden met een code, zoals vinkie. */
#[Layout('layouts::guest', ['width' => 'max-w-lg'])]
#[Title('Welkom')]
class Onboarding extends Component
{
    public string $name = '';

    public string $code = '';

    public function mount(): void
    {
        if (auth()->user()->households()->exists()) {
            $this->redirect('/');
        }
    }

    public function create(CreateHousehold $createHousehold): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255']]);

        $createHousehold->handle(auth()->user(), $this->name);

        session()->flash('toast', ['type' => 'success', 'message' => 'Huishouden aangemaakt!']);
        $this->redirect('/');
    }

    public function join(AcceptInvite $acceptInvite): void
    {
        if (trim($this->code) === '') {
            return;
        }

        try {
            $acceptInvite->handle(auth()->user(), $this->code);
        } catch (InviteException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        session()->flash('toast', ['type' => 'success', 'message' => 'Welkom in het huishouden!']);
        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.onboarding');
    }
}
