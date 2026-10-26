@props(['user'])

{{-- vinkie UserAvatar zonder foto: rondje met gradient en de eerste letter (foto's komen in M8) --}}
<span {{ $attributes->class('flex size-10 shrink-0 items-center justify-center rounded-full bg-gradient-warm font-bold text-primary-foreground') }}>
    {{ $user->initial() }}
</span>
