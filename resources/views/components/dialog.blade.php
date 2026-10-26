@props(['name', 'title'])

{{--
    Dialoog zoals vinkie (Radix Dialog, rounded-3xl). Openen: $dispatch('open-dialog', 'naam').
    Sluiten: Esc, klik buiten, of $this->dispatch('close-dialog') vanuit Livewire.
--}}
<div
    x-data="{ open: false }"
    x-on:open-dialog.window="if ($event.detail === @js($name)) open = true"
    x-on:close-dialog.window="open = false"
    x-on:keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 p-4"
        x-on:click.self="open = false"
    >
        <div
            x-show="open"
            x-transition.scale.95.duration.150ms
            class="w-full max-w-md rounded-3xl bg-card p-6 shadow-soft"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $title }}"
        >
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">{{ $title }}</h2>
                <x-button variant="icon" x-on:click="open = false">
                    <x-icon name="x" />
                    <span class="sr-only">Sluiten</span>
                </x-button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
