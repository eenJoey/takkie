<div class="grid max-w-lg items-start gap-6 lg:max-w-none lg:grid-cols-2">
    <x-page-header title="Profiel" subtitle="Beheer je gegevens en voorkeuren." class="lg:col-span-2" />

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

    <a href="{{ route('hero') }}" wire:navigate class="group flex items-center gap-4 rounded-3xl bg-card p-6 shadow-card transition hover:ring-2 hover:ring-primary/30">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-2xl" aria-hidden="true">⚔️</span>
        <span class="min-w-0 flex-1">
            <span class="block font-bold">Mijn held</span>
            <span class="block text-sm text-muted-foreground">Held-level {{ $heroLevel }} · levels, koken en prestige</span>
        </span>
        <x-icon name="chevron-right" class="size-5 text-muted-foreground transition group-hover:translate-x-0.5" />
    </a>

    <section class="space-y-3 rounded-3xl bg-card p-6 shadow-card">
        <div>
            <h2 class="font-bold">Starttabblad</h2>
            <p class="text-sm text-muted-foreground">Dit tabblad wordt geopend als je Takkie start.</p>
        </div>
        <x-select aria-label="Starttabblad" x-data x-on:change="$wire.setStartPage($event.target.value)">
            @foreach (\App\Enums\StartPage::cases() as $page)
                <option value="{{ $page->value }}" @selected($user->startPage() === $page)>{{ $page->label() }}</option>
            @endforeach
        </x-select>
    </section>

    <section class="space-y-3 rounded-3xl bg-card p-6 shadow-card">
        <div>
            <h2 class="font-bold">Kleuren</h2>
            <p class="text-sm text-muted-foreground">Alleen voor jou; anderen in je huishouden houden hun eigen kleuren.</p>
        </div>
        {{-- zoals Slack: kaartjes zonder namen, met een rondje (vinkje = gekozen) en een kleurbalkje --}}
        <div role="radiogroup" aria-label="Kleuren" class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Enums\Theme::cases() as $theme)
                @php $selected = $user->theme() === $theme; @endphp
                <button type="button" role="radio" aria-checked="{{ $selected ? 'true' : 'false' }}" aria-label="Kleuren {{ $loop->iteration }}"
                    wire:key="theme-{{ $theme->value }}" wire:click="setTheme('{{ $theme->value }}')"
                    @class([
                        'flex items-center gap-3 rounded-2xl border-2 p-3 text-left transition',
                        'border-primary' => $selected,
                        'border-border hover:border-border-strong' => ! $selected,
                    ])>
                    <span @class([
                        'flex size-6 shrink-0 items-center justify-center rounded-full border-2',
                        'border-primary bg-primary text-primary-foreground' => $selected,
                        'border-border-strong' => ! $selected,
                    ])>
                        @if ($selected)
                            <x-icon name="check" class="size-3.5" />
                        @endif
                    </span>
                    {{-- de kleuren van die stelling zelf: data-theme zet de tokens alleen binnen dit balkje --}}
                    <span data-theme="{{ $theme->value }}" class="flex h-8 flex-1 overflow-hidden rounded-full border border-border-strong">
                        <span class="flex-1 bg-background"></span>
                        <span class="flex-1 bg-sidebar"></span>
                        <span class="flex-1 bg-secondary"></span>
                        <span class="flex-1 bg-accent"></span>
                        <span class="flex-1 bg-primary"></span>
                        <span class="flex-1 bg-foreground"></span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="space-y-3 rounded-3xl bg-card p-6 shadow-card">
        <div>
            <h2 class="font-bold">Huishoudens</h2>
            <p class="text-sm text-muted-foreground">Je huidige huishouden wordt ook geopend als je Takkie start.</p>
        </div>
        <ul class="space-y-2">
            @foreach ($this->households as $household)
                @php $isCurrent = $current?->id === $household->id; @endphp
                <li wire:key="hh-{{ $household->id }}" @class([
                    'flex items-center gap-3 rounded-2xl border px-4 py-3',
                    'border-primary bg-primary/10' => $isCurrent,
                    'border-border' => ! $isCurrent,
                ])>
                    <x-icon :name="$isCurrent ? 'check' : 'users'" @class(['size-5 shrink-0', 'text-primary' => $isCurrent, 'text-muted-foreground' => ! $isCurrent]) />
                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $household->name }}</span>
                    @if ($isCurrent)
                        <x-button size="sm" variant="secondary" class="rounded-full" :href="route('household')" wire:navigate>Bekijken</x-button>
                    @else
                        <x-button size="sm" variant="outline" class="rounded-full" wire:click="switchHousehold({{ $household->id }})"
                            wire:loading.attr="disabled" wire:target="switchHousehold">Wisselen</x-button>
                    @endif
                </li>
            @endforeach
        </ul>
        <a href="{{ route('household') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-link hover:underline">
            <x-icon name="plus" class="size-4" />
            Nieuw huishouden of uitnodiging
        </a>
    </section>

    <livewire:profile.notification-settings />

    <form method="POST" action="{{ route('logout') }}" class="lg:col-span-2">
        @csrf
        <x-button type="submit" variant="outline" class="rounded-full">
            <x-icon name="log-out" />
            Uitloggen
        </x-button>
    </form>
</div>
