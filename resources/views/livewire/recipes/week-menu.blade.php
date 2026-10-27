<div>
    <x-page-header title="Weekmenu" subtitle="Wat eten we deze week? Kies een recept of vul zelf iets in.">
        <x-slot:actions>
            <x-button size="sm" variant="secondary" :href="route('recipes.index')" wire:navigate>
                <x-icon name="book-open" />
                Recepten
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @php $days = $this->days; @endphp
    <div class="mb-4 flex items-center justify-between gap-2">
        <button type="button" wire:click="previousWeek" aria-label="Vorige week"
            class="flex size-9 items-center justify-center rounded-full bg-card shadow-card hover:bg-accent/30">
            <x-icon name="chevron-left" />
        </button>
        <div class="text-center">
            <h2 class="text-lg font-bold">
                {{ $days[0]->locale('nl')->translatedFormat('j M') }} – {{ $days[6]->locale('nl')->translatedFormat('j M') }}
            </h2>
            @if ($weekOffset !== 0)
                <button type="button" wire:click="thisWeek" class="text-xs font-semibold text-link hover:underline">Naar deze week</button>
            @endif
        </div>
        <button type="button" wire:click="nextWeek" aria-label="Volgende week"
            class="flex size-9 items-center justify-center rounded-full bg-card shadow-card hover:bg-accent/30">
            <x-icon name="chevron-right" />
        </button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($days as $day)
            @php
                $date = $day->toDateString();
                $entry = $this->entries->get($date);
                $isToday = $date === $today;
            @endphp
            <x-card wire:key="day-{{ $date }}" @class(['flex flex-col gap-3', 'ring-2 ring-primary' => $isToday])>
                <div class="flex items-baseline justify-between gap-2">
                    <h3 class="font-bold first-letter:uppercase">{{ $day->locale('nl')->translatedFormat('l') }}</h3>
                    <span class="text-sm text-muted-foreground">{{ $isToday ? 'vandaag' : $day->locale('nl')->translatedFormat('j M') }}</span>
                </div>

                @if ($entry)
                    <div class="flex items-center gap-2 rounded-xl border border-item-border bg-item px-3 py-2.5">
                        <span class="text-lg" aria-hidden="true">🍽️</span>
                        @if ($entry->recipe)
                            <a href="{{ route('recipes.show', $entry->recipe->slug) }}" wire:navigate class="min-w-0 flex-1 truncate font-semibold hover:underline">{{ $entry->recipe->title }}</a>
                        @else
                            <span class="min-w-0 flex-1 truncate font-semibold">{{ $entry->title }}</span>
                        @endif
                        <x-button variant="danger-icon" wire:click="clearDay('{{ $date }}')">
                            <x-icon name="x" />
                            <span class="sr-only">Leeg maken</span>
                        </x-button>
                    </div>
                @else
                    {{-- dropdown met de eigen recepten, of vrij invullen --}}
                    <div x-data="{ free: false, text: '' }" class="space-y-2">
                        <x-select x-show="! free" aria-label="Gerecht voor {{ $day->locale('nl')->translatedFormat('l') }}"
                            x-on:change="if ($event.target.value === 'free') { free = true; $nextTick(() => $refs.text.focus()) } else if ($event.target.value) { $wire.chooseRecipe('{{ $date }}', Number($event.target.value)) }">
                            <option value="">Kies een recept…</option>
                            @foreach ($this->recipes as $recipe)
                                <option value="{{ $recipe->id }}">{{ $recipe->title }}</option>
                            @endforeach
                            <option value="free">✏️ Zelf invullen…</option>
                        </x-select>
                        <form x-show="free" x-cloak class="flex gap-2"
                            x-on:submit.prevent="if (text.trim()) $wire.chooseFree('{{ $date }}', text)">
                            <x-input x-ref="text" x-model="text" placeholder="Bijv. friet halen" maxlength="255" />
                            <x-button type="submit" class="min-w-11">
                                <x-icon name="check" />
                                <span class="sr-only">Opslaan</span>
                            </x-button>
                            <x-button type="button" variant="secondary" class="min-w-11" x-on:click="free = false; text = ''">
                                <x-icon name="x" />
                                <span class="sr-only">Annuleren</span>
                            </x-button>
                        </form>
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>
</div>
