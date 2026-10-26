@props(['value', 'size' => 'sm'])

{{-- vinkie puntenpill: Sparkles + aantal --}}
<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-0.5 rounded-full bg-warning/20 font-bold text-warning-foreground tabular-nums',
    'px-2 py-0.5 text-xs' => $size === 'sm',
    'px-2.5 py-1 text-sm' => $size === 'md',
]) }}>
    <x-icon name="sparkles" @class(['size-3' => $size === 'sm', 'size-3.5' => $size === 'md']) />
    {{ $value }}
</span>
