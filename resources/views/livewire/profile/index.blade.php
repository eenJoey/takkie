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
        <a href="{{ route('household') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-primary-foreground hover:underline">
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
