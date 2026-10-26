@props(['title', 'subtitle' => null])

<div class="mb-4 flex items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="truncate text-2xl font-extrabold">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
