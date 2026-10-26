<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /login: één scherm met de tabs "Inloggen" | "Account maken", 1-op-1 zoals vinkie (MIGRATIE-PLAN.md, sectie b).
 * Altijd "onthouden" (vinkie bleef ingelogd tot uitloggen). Geen e-mailverificatie (Q3).
 */
#[Layout('layouts::guest')]
#[Title('Inloggen')]
class Login extends Component
{
    public string $mode = 'signin';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'signup' ? 'signup' : 'signin';
        $this->resetErrorBag();
    }

    public function submit(): void
    {
        try {
            $this->mode === 'signup' ? $this->register() : $this->login();
        } catch (ValidationException $e) {
            // vinkie toonde fouten als toast
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());

            throw $e;
        }
    }

    private function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt(['email' => Str::lower($this->email), 'password' => $this->password], remember: true)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirect('/');
    }

    private function register(): void
    {
        $this->email = Str::lower(trim($this->email));

        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => trim($data['name'] ?? '') ?: Str::before($data['email'], '@'),
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Registered($user));

        Auth::login($user, remember: true);
        session()->regenerate();
        session()->flash('toast', ['type' => 'success', 'message' => 'Account aangemaakt!']);

        $this->redirect('/');
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
