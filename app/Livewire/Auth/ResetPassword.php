<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /reset-password/{token}?email=…, zoals vinkie: één veld "Nieuw wachtwoord" (geen bevestiging).
 * Na succes ingelogd (vinkie gebruikte de recovery-sessie) en naar home.
 */
#[Layout('layouts::guest')]
#[Title('Nieuw wachtwoord')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = Str::lower((string) request()->query('email', ''));
    }

    public function save(): void
    {
        $this->validate(['password' => ['required', 'string', 'min:6']]);

        $resetUser = null;

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'token' => $this->token],
            function (User $user, string $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->dispatch('toast', type: 'error', message: __($status));

            return;
        }

        Auth::login($resetUser, remember: true);
        session()->regenerate();
        session()->flash('toast', ['type' => 'success', 'message' => 'Wachtwoord aangepast!']);

        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
