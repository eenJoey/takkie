<div class="space-y-6">
    <x-page-header title="Beloningen" subtitle="Wissel je punten in voor iets leuks 🎁">
        <x-slot:actions>
            <x-button class="rounded-full shadow-card" wire:click="openNew">
                <x-icon name="plus" />
                Nieuw
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->rewards->isEmpty())
        <x-empty-state emoji="🎁" title="Nog geen beloningen" description="Maak je eerste beloning: bv. '1 uur gamen — 50 punten'." />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->rewards as $reward)
                @php $canAfford = $this->balance >= $reward->cost_points; @endphp
                <div wire:key="reward-{{ $reward->id }}" class="flex flex-col rounded-2xl bg-card p-5 shadow-card">
                    <div class="flex items-start justify-between">
                        <div class="text-4xl">{{ $reward->icon ?: '🎁' }}</div>
                        <button type="button" wire:click="delete({{ $reward->id }})" class="p-1 text-muted-foreground hover:text-destructive" aria-label="Verwijderen">
                            <x-icon name="trash" />
                        </button>
                    </div>
                    <h3 class="mt-2 font-bold break-words">{{ $reward->title }}</h3>
                    @if ($reward->description)
                        <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ $reward->description }}</p>
                    @endif
                    <div class="flex-1"></div>
                    <div class="mt-4 flex items-center justify-between border-t border-border pt-3">
                        <x-points :value="$reward->cost_points" size="md" />
                        <x-button size="sm" class="rounded-full" wire:click="redeem({{ $reward->id }})" :disabled="! $canAfford">
                            {{ $canAfford ? 'Inwisselen' : 'Te duur' }}
                        </x-button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->leaderboard->isNotEmpty())
        @php $medals = ['🥇', '🥈', '🥉']; @endphp
        <section>
            <h2 class="mb-2 flex items-center gap-2 text-lg font-bold">
                <x-icon name="trophy" class="size-5 text-warning" /> Scorebord
            </h2>
            <div class="divide-y divide-border overflow-hidden rounded-2xl bg-card shadow-card">
                @foreach ($this->leaderboard as $index => $member)
                    <div wire:key="rank-{{ $member->id }}" @class([
                        'flex items-center gap-3 px-3 py-2',
                        'bg-primary/5' => $member->id === auth()->id(),
                    ])>
                        <span class="w-7 text-center text-base">{{ $medals[$index] ?? '#'.($index + 1) }}</span>
                        <x-avatar :user="$member" class="size-8 text-xs" />
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="text-xs text-muted-foreground"> (jij)</span>
                            @endif
                        </span>
                        <x-points :value="$member->pivot->points_balance" size="md" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($this->history->isNotEmpty())
        <section>
            <h2 class="mb-3 text-lg font-bold">Jouw recente inwisselingen</h2>
            <div class="divide-y divide-border rounded-2xl bg-card shadow-card">
                @foreach ($this->history as $redemption)
                    <div wire:key="redemption-{{ $redemption->id }}" class="flex items-center gap-3 px-4 py-3">
                        <span class="text-xl">{{ $redemption->reward?->icon ?: '🎁' }}</span>
                        <span class="min-w-0 flex-1 truncate font-medium">{{ $redemption->reward?->title }}</span>
                        <span class="text-sm text-muted-foreground">{{ $redemption->redeemed_at->timezone(config('app.local_timezone'))->format('j-n-Y') }}</span>
                        <span class="font-bold text-destructive">-{{ $redemption->points_spent }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-dialog name="reward-form" title="Nieuwe beloning">
        <form wire:submit="create" class="space-y-3">
            <div>
                <x-label for="reward-title">Titel</x-label>
                <x-input id="reward-title" wire:model="title" required maxlength="120" placeholder="1 uur gamen" />
                @error('title') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="reward-description">Beschrijving</x-label>
                <x-textarea id="reward-description" wire:model="description" />
            </div>
            <div>
                <x-label for="reward-cost">Punten kosten</x-label>
                <x-input id="reward-cost" type="number" min="0" wire:model="costPoints" />
                @error('costPoints') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label>Icoon</x-label>
                <div class="mt-1 flex flex-wrap gap-1.5">
                    @foreach (\App\Models\Reward::ICONS as $option)
                        <button type="button" wire:click="$set('icon', @js($option))" aria-label="Icoon {{ $option }}" @class([
                            'size-10 rounded-xl text-xl transition',
                            'bg-primary/20 ring-2 ring-primary' => $icon === $option,
                            'bg-muted hover:bg-accent/30' => $icon !== $option,
                        ])>{{ $option }}</button>
                    @endforeach
                </div>
            </div>
            <x-button type="submit" class="w-full rounded-full" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">Aanmaken</span>
                <span wire:loading wire:target="create">Opslaan…</span>
            </x-button>
        </form>
    </x-dialog>
</div>
