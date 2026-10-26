<div>
    <div class="mb-8 text-center">
        <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="mx-auto mb-3 h-auto w-32">
        <p class="mt-1 text-muted-foreground">Samen het huishouden draaiend houden</p>
    </div>

    <div class="rounded-3xl bg-card p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex gap-2 rounded-full bg-muted p-1">
            <button type="button" wire:click="setMode('signin')" @class([
                'flex-1 rounded-full py-2 text-sm font-semibold transition',
                'bg-card text-foreground shadow-sm' => $mode === 'signin',
                'text-muted-foreground' => $mode !== 'signin',
            ])>Inloggen</button>
            <button type="button" wire:click="setMode('signup')" @class([
                'flex-1 rounded-full py-2 text-sm font-semibold transition',
                'bg-card text-foreground shadow-sm' => $mode === 'signup',
                'text-muted-foreground' => $mode !== 'signup',
            ])>Account maken</button>
        </div>

        <form wire:submit="submit" class="space-y-4">
            @if ($mode === 'signup')
                <div>
                    <x-label for="name">Naam</x-label>
                    <x-input id="name" wire:model="name" placeholder="Hoe heet je?" autocomplete="name" />
                </div>
            @endif
            <div>
                <x-label for="email">E-mailadres</x-label>
                <x-input id="email" type="email" wire:model="email" required autocomplete="email" />
            </div>
            <div>
                <x-label for="password">Wachtwoord</x-label>
                <x-input id="password" type="password" wire:model="password" required minlength="6"
                    :autocomplete="$mode === 'signup' ? 'new-password' : 'current-password'" />
            </div>
            <x-button type="submit" size="lg" class="w-full rounded-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">{{ $mode === 'signup' ? 'Account aanmaken' : 'Inloggen' }}</span>
                <span wire:loading wire:target="submit">Even geduld…</span>
            </x-button>
        </form>

        {{-- "Doorgaan met Google" komt hier in M8 (Socialite) --}}

        <div class="mt-5 text-center text-sm">
            <a href="{{ route('password.request') }}" wire:navigate class="text-muted-foreground hover:text-primary">Wachtwoord vergeten?</a>
        </div>
    </div>
</div>
