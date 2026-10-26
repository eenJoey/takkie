<div>
    <div class="mb-8 text-center">
        <div class="mb-3 text-5xl">🏡</div>
        <h1 class="text-3xl font-extrabold">Welkom bij Takkie!</h1>
        <p class="mt-1 text-muted-foreground">Maak een huishouden of word lid van een bestaand huishouden.</p>
    </div>

    <div class="mb-4 rounded-3xl bg-card p-6 shadow-soft">
        <h2 class="mb-3 font-bold">Nieuw huishouden maken</h2>
        <form wire:submit="create" class="space-y-3">
            <div>
                <x-label for="hname">Naam</x-label>
                <x-input id="hname" wire:model="name" required maxlength="255" placeholder="Familie Jansen" />
            </div>
            <x-button type="submit" class="w-full rounded-full" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">Aanmaken</span>
                <span wire:loading wire:target="create">Bezig…</span>
            </x-button>
        </form>
    </div>

    <div class="rounded-3xl bg-card p-6 shadow-soft">
        <h2 class="mb-3 font-bold">Lid worden via uitnodigingscode</h2>
        <form wire:submit="join" class="space-y-3">
            <div>
                <x-label for="code">Code</x-label>
                <x-input id="code" wire:model="code" placeholder="Plak hier de code" />
            </div>
            <x-button type="submit" variant="outline" class="w-full rounded-full">Toetreden</x-button>
        </form>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm text-muted-foreground hover:text-primary">Uitloggen</button>
    </form>
</div>
