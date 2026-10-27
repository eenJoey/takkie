<div class="flex shrink-0 items-center gap-1.5">
    @if ($streak > 0)
        <div class="flex items-center gap-1 rounded-full bg-card px-3 py-1.5 font-bold shadow-card"
            title="{{ $streak }} {{ $streak === 1 ? 'dag' : 'dagen' }} op rij een taak gedaan">
            <span aria-hidden="true">🔥</span>
            <span class="tabular-nums">{{ $streak }}</span>
            <span class="sr-only">{{ $streak === 1 ? 'dag' : 'dagen' }} op rij</span>
        </div>
    @endif
    <div class="flex items-center gap-1.5 rounded-full bg-gradient-warm px-3.5 py-1.5 font-bold text-primary-foreground shadow-card" title="Jouw punten">
        <x-icon name="sparkles" />
        <span class="tabular-nums">{{ $points }}</span>
        <span class="sr-only">punten</span>
    </div>
</div>
