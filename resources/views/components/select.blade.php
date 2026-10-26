{{-- Native select in de stijl van x-input (vervangt vinkie's Radix Select) --}}
<select {{ $attributes->class('h-10 w-full rounded-xl border border-input bg-card px-3 text-base transition focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/25 md:text-sm') }}>
    {{ $slot }}
</select>
