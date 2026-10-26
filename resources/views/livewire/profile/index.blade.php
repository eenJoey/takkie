<div class="max-w-lg space-y-6">
    <x-page-header title="Profiel" subtitle="Beheer je gegevens en voorkeuren." />

    <section class="space-y-5 rounded-3xl bg-card p-6 shadow-card">
        <div class="flex items-center gap-4" x-data>
            <button type="button" x-on:click="$refs.photo.click()" class="group relative rounded-full" aria-label="Profielfoto wijzigen">
                <x-avatar :user="$user" class="size-20 text-2xl" />
                <span class="absolute inset-0 flex items-center justify-center rounded-full bg-foreground/40 opacity-0 transition group-hover:opacity-100">
                    <x-icon name="camera" class="size-6 text-background" />
                </span>
            </button>
            <div>
                <x-button variant="outline" class="rounded-full" x-on:click="$refs.photo.click()" wire:loading.attr="disabled" wire:target="photo">
                    <span wire:loading.remove wire:target="photo">Foto wijzigen</span>
                    <span wire:loading wire:target="photo">Bezig…</span>
                </x-button>
                <p class="mt-1 text-xs text-muted-foreground">JPG, PNG of WebP · max 5 MB</p>
            </div>
            <input x-ref="photo" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" wire:model="photo">
        </div>

        <form wire:submit="saveName" class="space-y-3">
            <div>
                <x-label for="dname">Weergavenaam</x-label>
                <x-input id="dname" wire:model="name" maxlength="60" />
            </div>
            <div>
                <x-label for="mail">E-mail</x-label>
                <x-input id="mail" :value="$user->email" disabled class="opacity-70" />
            </div>
            <x-button type="submit" class="rounded-full" wire:loading.attr="disabled" wire:target="saveName">
                <span wire:loading.remove wire:target="saveName">Opslaan</span>
                <span wire:loading wire:target="saveName">Bezig…</span>
            </x-button>
        </form>
    </section>

    <section class="space-y-3 rounded-3xl bg-card p-6 shadow-card">
        <div>
            <h2 class="font-bold">Standaard huishouden</h2>
            <p class="text-sm text-muted-foreground">Dit huishouden wordt geopend als je Takkie start.</p>
        </div>
        <x-select aria-label="Standaard huishouden" x-data x-on:change="$wire.setDefaultHousehold(Number($event.target.value))">
            @foreach ($this->households as $household)
                <option value="{{ $household->id }}" @selected($current?->id === $household->id)>{{ $household->name }}</option>
            @endforeach
        </x-select>
    </section>

    {{-- Meldingsinstellingen (vinkie NotificationSettings) volgen in M9 --}}

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-button type="submit" variant="outline" class="rounded-full">
            <x-icon name="log-out" />
            Uitloggen
        </x-button>
    </form>
</div>
