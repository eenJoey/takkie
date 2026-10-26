{{--
    Toasts boven in het midden, zoals vinkie (sonner, position="top-center", richColors).
    Bronnen: session('toast') na een redirect, of $this->dispatch('toast', type: 'success', message: '...') vanuit Livewire.
--}}
@php $flash = session('toast'); @endphp

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'success', message: toast.message });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }"
    x-init="@if ($flash) add(@js($flash)) @endif"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition.opacity.duration.200ms
            class="pointer-events-auto w-full max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-soft"
            :class="toast.type === 'error'
                ? 'border-destructive/30 bg-destructive-soft text-destructive'
                : 'border-success/30 bg-card text-foreground'"
            role="status"
            x-text="toast.message"
            x-on:click="remove(toast.id)"
        ></div>
    </template>
</div>
