@props(['user'])

{{-- vinkie UserAvatar: profielfoto als die er is, anders een rondje met gradient en de eerste letter --}}
@php $url = $user->avatarUrl(); @endphp

@if ($url)
    <img src="{{ $url }}" alt="" referrerpolicy="no-referrer"
        {{ $attributes->class('size-10 shrink-0 rounded-full bg-muted object-cover') }}>
@else
    <span {{ $attributes->class('flex size-10 shrink-0 items-center justify-center rounded-full bg-gradient-warm font-bold text-primary-foreground') }}>
        {{ $user->initial() }}
    </span>
@endif
