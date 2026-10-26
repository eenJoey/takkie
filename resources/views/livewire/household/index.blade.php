<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-extrabold">{{ $this->household->name }}</h1>
            <p class="text-sm text-muted-foreground">{{ $this->members->count() }} {{ $this->members->count() === 1 ? 'lid' : 'leden' }}</p>
        </div>
        <x-button variant="outline" class="shrink-0 rounded-full" x-on:click="$dispatch('open-dialog', 'new-household')">
            <x-icon name="plus" />
            Nieuw huishouden
        </x-button>
    </div>

    <section>
        <h2 class="mb-3 text-lg font-bold">Leden</h2>
        <div class="divide-y divide-border rounded-2xl bg-card shadow-card">
            @foreach ($this->members as $member)
                <div wire:key="member-{{ $member->id }}" class="flex items-center gap-3 px-4 py-3">
                    <x-avatar :user="$member" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="text-xs text-muted-foreground">(jij)</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-muted-foreground">{{ $member->email }}</p>
                    </div>
                    @if ($member->pivot->role === \App\Enums\HouseholdRole::Owner)
                        <x-icon name="crown" class="size-4 text-warning" />
                        <span class="sr-only">Eigenaar</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    @if ($this->isOwner)
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold">Uitnodigingen</h2>
                <x-button size="sm" class="rounded-full" wire:click="createInvite">
                    <x-icon name="user-plus" />
                    Nieuwe link
                </x-button>
            </div>

            @if ($this->invites->isEmpty())
                <p class="text-sm text-muted-foreground">Nog geen openstaande uitnodigingen.</p>
            @else
                <div class="space-y-2">
                    @foreach ($this->invites as $invite)
                        <div wire:key="invite-{{ $invite->id }}" class="flex items-center gap-3 rounded-2xl bg-card p-3 shadow-card">
                            <code class="min-w-0 flex-1 truncate rounded-lg bg-muted px-2 py-1.5 text-xs">{{ $invite->url() }}</code>
                            <x-button size="sm" variant="outline" class="rounded-full" data-url="{{ $invite->url() }}"
                                x-on:click="navigator.clipboard.writeText($el.dataset.url)
                                    .then(() => $dispatch('toast', { type: 'success', message: 'Link gekopieerd!' }))
                                    .catch(() => $dispatch('toast', { type: 'error', message: 'Kopiëren lukte niet. Selecteer de link en kopieer hem zelf.' }))">
                                <x-icon name="copy" class="size-3.5" />
                                <span class="sr-only">Link kopiëren</span>
                            </x-button>
                        </div>
                    @endforeach
                </div>
            @endif

            <form wire:submit="inviteByEmail" class="mt-4 flex gap-2">
                <x-input type="email" wire:model="inviteEmail" placeholder="email@voorbeeld.nl" required />
                <x-button type="submit" class="rounded-full">Uitnodigen</x-button>
            </form>
        </section>
    @else
        <x-button variant="outline" class="rounded-full" wire:click="leave"
            wire:confirm="Weet je zeker dat je dit huishouden wilt verlaten?">
            <x-icon name="log-out" />
            Huishouden verlaten
        </x-button>
    @endif

    <x-dialog name="new-household" title="Nieuw huishouden">
        <form wire:submit="createHousehold" class="space-y-3">
            <div>
                <x-label for="new-household-name">Naam</x-label>
                <x-input id="new-household-name" wire:model="newHouseholdName" required maxlength="255" />
            </div>
            <x-button type="submit" class="w-full rounded-full" wire:loading.attr="disabled" wire:target="createHousehold">
                <span wire:loading.remove wire:target="createHousehold">Aanmaken</span>
                <span wire:loading wire:target="createHousehold">Bezig…</span>
            </x-button>
        </form>
    </x-dialog>
</div>
