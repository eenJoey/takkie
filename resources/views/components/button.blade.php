@props(['variant' => 'primary', 'size' => 'default', 'href' => null])

@php
    // Varianten volgen vinkie's shadcn-knop, aangevuld met de takkie-knoppen (ghost-dashed, icon, danger-icon)
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm',
        'secondary' => 'rounded-xl bg-secondary text-muted-foreground hover:bg-accent/60',
        'outline' => 'rounded-xl border border-input bg-card hover:bg-accent/40',
        'ghost-dashed' => 'w-full rounded-xl border-2 border-dashed border-border-strong text-muted-foreground hover:bg-secondary hover:text-foreground',
        'icon' => 'size-8 rounded-[10px] border border-transparent text-muted-foreground hover:bg-secondary hover:border-border',
        'danger-icon' => 'size-8 rounded-[10px] border border-transparent text-destructive hover:bg-destructive-soft hover:border-destructive/20',
    ];

    $sizes = [
        'default' => in_array($variant, ['icon', 'danger-icon']) ? '' : 'h-10 px-4 text-sm',
        'sm' => in_array($variant, ['icon', 'danger-icon']) ? '' : 'h-8 px-3 text-sm',
        'lg' => 'h-11 px-6 text-base',
    ];

    $classes = $base.' '.$variants[$variant].' '.$sizes[$size];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
