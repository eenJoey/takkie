@props(['emoji' => '✨', 'title', 'description' => null])

<div {{ $attributes->class('px-4 py-10 text-center') }}>
    <div class="mb-3 text-4xl">{{ $emoji }}</div>
    <p class="font-bold">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-muted-foreground">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
