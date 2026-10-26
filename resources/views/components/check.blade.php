@props(['done' => false])

{{-- Afvinkknop in takkie-stijl: rondje, gevuld als afgevinkt --}}
<button type="button" {{ $attributes->class([
    'grid size-8 shrink-0 place-items-center rounded-full border-2 transition hover:scale-105',
    'border-primary bg-primary text-primary-foreground' => $done,
    'border-border bg-card text-transparent hover:border-primary' => ! $done,
]) }} aria-pressed="{{ $done ? 'true' : 'false' }}">
    <x-icon name="check" class="size-4" />
    <span class="sr-only">{{ $done ? 'Markeer als niet gedaan' : 'Markeer als gedaan' }}</span>
</button>
