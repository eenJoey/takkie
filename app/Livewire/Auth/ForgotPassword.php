<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /forgot-password, zoals vinkie. Toont altijd dezelfde succestekst,
 * zodat niet uitlekt of er een account met dit e-mailadres bestaat.
 */
#[Layout('layouts::guest')]
#[Title('Wachtwoord vergeten')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink(['email' => Str::lower(trim($this->email))]);

        if ($status === Password::RESET_THROTTLED) {
            $this->dispatch('toast', type: 'error', message: __($status));

            return;
        }

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
