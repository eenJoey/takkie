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

        <div class="my-5 flex items-center gap-3">
            <div class="h-px flex-1 bg-border"></div>
            <span class="text-xs text-muted-foreground">of</span>
            <div class="h-px flex-1 bg-border"></div>
        </div>

        {{-- Gewone link (geen wire:navigate): Google moet een volledige paginawissel krijgen --}}
        <x-button variant="outline" size="lg" class="w-full rounded-full" :href="route('auth.google')">
            <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.83z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Doorgaan met Google
        </x-button>

        <div class="mt-5 text-center text-sm">
            <a href="{{ route('password.request') }}" wire:navigate class="text-muted-foreground hover:text-primary">Wachtwoord vergeten?</a>
        </div>
    </div>
</div>
