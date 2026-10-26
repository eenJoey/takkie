<div class="rounded-3xl bg-card p-8 shadow-soft">
    <h1 class="mb-6 text-2xl font-bold">Nieuw wachtwoord</h1>

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-label for="password">Nieuw wachtwoord</x-label>
            <x-input id="password" type="password" wire:model="password" required minlength="6" autocomplete="new-password" />
        </div>
        <x-button type="submit" size="lg" class="w-full rounded-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Wachtwoord opslaan</span>
            <span wire:loading wire:target="save">Opslaan…</span>
        </x-button>
    </form>
</div>
