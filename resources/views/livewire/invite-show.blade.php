<div class="rounded-3xl bg-card p-8 text-center shadow-soft">
    <div class="mb-3 text-5xl">📬</div>

    @if (! $invite)
        <p class="text-muted-foreground">Uitnodiging niet gevonden of verlopen.</p>
    @else
        <h1 class="mb-2 text-xl font-bold">Je bent uitgenodigd!</h1>
        <p class="mb-6 text-muted-foreground">Doe mee aan <strong>{{ $invite->household->name }}</strong>.</p>

        @guest
            <x-button :href="route('login')" class="w-full rounded-full">Eerst inloggen</x-button>
        @else
            <x-button wire:click="accept" class="w-full rounded-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="accept">Accepteren</span>
                <span wire:loading wire:target="accept">Bezig…</span>
            </x-button>
        @endguest
    @endif
</div>
