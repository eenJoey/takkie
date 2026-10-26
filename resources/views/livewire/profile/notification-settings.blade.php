<section
    class="space-y-4 rounded-3xl bg-card p-6 shadow-card"
    x-data="takkieNotifications({ vapidPublicKey: @js($vapidPublicKey) })"
    x-init="init()"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="flex items-center gap-2 font-bold">
                <x-icon name="bell" /> Meldingen
            </h2>
            <p class="text-sm text-muted-foreground">Ontvang een melding op de dagen dat er taken openstaan.</p>
        </div>

        {{-- Schakelaar (vervangt vinkie's Radix Switch) --}}
        <button type="button" role="switch" :aria-checked="$wire.enabled ? 'true' : 'false'" aria-label="Meldingen aan of uit"
            x-on:click="toggle()" :disabled="! supported || busy"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-50"
            :class="$wire.enabled ? 'bg-primary' : 'bg-input'">
            <span class="inline-block size-5 rounded-full bg-card shadow transition" :class="$wire.enabled ? 'translate-x-5' : 'translate-x-0.5'"></span>
        </button>
    </div>

    <p x-show="! supported" x-cloak class="text-sm text-destructive">
        Dit apparaat/browser ondersteunt geen pushmeldingen. Installeer Takkie als app (Delen → Zet op beginscherm) en probeer opnieuw.
    </p>

    @if ($enabled)
        <div class="space-y-2">
            <x-label>Meldingsmomenten ({{ count($times) }}/{{ $max }})</x-label>
            @foreach ($times as $index => $time)
                <div wire:key="time-{{ $index }}" class="flex items-center gap-2">
                    <x-input type="time" wire:model="times.{{ $index }}" class="w-36" aria-label="Meldingsmoment {{ $index + 1 }}" />
                    @if (count($times) > 1)
                        <button type="button" wire:click="removeTime({{ $index }})" class="p-1 text-muted-foreground hover:text-destructive" aria-label="Moment verwijderen">
                            <x-icon name="x" />
                        </button>
                    @endif
                </div>
                @error('times.'.$index) <p class="text-sm text-destructive">{{ $message }}</p> @enderror
            @endforeach
            @if (count($times) < $max)
                <x-button variant="outline" size="sm" class="rounded-full" wire:click="addTime">
                    <x-icon name="plus" />
                    Moment toevoegen
                </x-button>
            @endif
        </div>

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" wire:model="onlyAssigned" class="size-4 accent-primary">
            Alleen taken die aan mij zijn toegewezen
        </label>

        <div class="flex flex-wrap gap-2">
            <x-button class="rounded-full" x-on:click="$wire.save(timezone())" x-bind:disabled="busy">Opslaan</x-button>
            <template x-if="deviceOn">
                <x-button variant="outline" class="rounded-full" x-on:click="turnOffDevice()" x-bind:disabled="busy">Dit apparaat uitzetten</x-button>
            </template>
        </div>
    @endif
</section>
