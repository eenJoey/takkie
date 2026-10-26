<div class="rounded-3xl bg-card p-8 shadow-soft">
    <h1 class="mb-2 text-2xl font-bold">Wachtwoord vergeten?</h1>
    <p class="mb-6 text-muted-foreground">Geen probleem, we sturen je een reset link.</p>

    @if ($sent)
        <p class="font-medium text-success">Check je inbox voor de reset link.</p>
    @else
        <form wire:submit="send" class="space-y-4">
            <div>
                <x-label for="email">E-mailadres</x-label>
                <x-input id="email" type="email" wire:model="email" required autocomplete="email" />
            </div>
            <x-button type="submit" size="lg" class="w-full rounded-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="send">Reset link sturen</span>
                <span wire:loading wire:target="send">Versturen…</span>
            </x-button>
        </form>
    @endif

    <div class="mt-5 text-center">
        <a href="{{ route('login') }}" wire:navigate class="text-sm text-muted-foreground hover:text-primary">← Terug naar inloggen</a>
    </div>
</div>
