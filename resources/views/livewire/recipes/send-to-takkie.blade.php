<div class="max-w-3xl space-y-4">
    <x-page-header title="Naar Takkie" subtitle="Recepten overnemen van elke site, ook als die ophalen blokkeert (zoals AH).">
        <x-slot:actions>
            <x-button size="sm" variant="secondary" :href="route('recipes.create')" wire:navigate>
                <x-icon name="arrow-left" />
                Terug
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="space-y-2 text-sm">
        <p>Je opent het recept gewoon in je eigen browser. "Naar Takkie" leest het recept uit die pagina en opent Takkie met alles ingevuld. Jij controleert en slaat op.</p>
    </x-card>

    <x-card class="space-y-3">
        <h2 class="font-bold">💻 Computer</h2>
        <p class="text-sm">Sleep deze knop naar je bladwijzerbalk. Open daarna een recept en klik op de bladwijzer.</p>
        <a href="{{ $bookmarklet }}" x-on:click.prevent="$dispatch('toast', { type: 'success', message: 'Sleep de knop naar je bladwijzerbalk' })"
            class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 font-semibold text-primary-foreground shadow-card">
            <x-icon name="link" />
            Naar Takkie
        </a>
    </x-card>

    <x-card class="space-y-3">
        <h2 class="font-bold">🤖 Android (Chrome)</h2>
        <ol class="list-decimal space-y-1 pl-5 text-sm">
            <li>Kopieer de code hieronder.</li>
            <li>Maak een bladwijzer van een willekeurige pagina en bewerk hem: naam <strong>Naar Takkie</strong>, plak de code als URL.</li>
            <li>Open een recept, typ <strong>Naar Takkie</strong> in de adresbalk en tik op de bladwijzer.</li>
        </ol>
        <x-button size="sm" variant="outline" class="rounded-full" data-code="{{ $bookmarklet }}"
            x-on:click="navigator.clipboard.writeText($el.dataset.code)
                .then(() => $dispatch('toast', { type: 'success', message: 'Code gekopieerd' }))
                .catch(() => $dispatch('toast', { type: 'error', message: 'Kopiëren lukte niet' }))">
            <x-icon name="copy" class="size-3.5" />
            Bladwijzer-code kopiëren
        </x-button>
    </x-card>

    <x-card class="space-y-3">
        <h2 class="font-bold">📱 iPhone (Safari)</h2>
        <ol class="list-decimal space-y-1 pl-5 text-sm">
            <li>Open de app <strong>Opdrachten</strong> en maak een nieuwe opdracht met de naam <strong>Naar Takkie</strong>.</li>
            <li>Tik op <strong>ⓘ</strong> en zet <strong>Toon in deelpaneel</strong> aan (invoer: Safari-webpagina's).</li>
            <li>Voeg de actie <strong>Voer JavaScript uit op webpagina</strong> toe en vervang de code door de code hieronder.</li>
            <li>Voeg daarna de actie <strong>Open URL's</strong> toe (met het JavaScript-resultaat).</li>
            <li>Open een recept in Safari, tik op <strong>Deel</strong> en kies <strong>Naar Takkie</strong>.</li>
        </ol>
        <x-button size="sm" variant="outline" class="rounded-full" data-code="{{ $shortcutScript }}"
            x-on:click="navigator.clipboard.writeText($el.dataset.code)
                .then(() => $dispatch('toast', { type: 'success', message: 'Code gekopieerd' }))
                .catch(() => $dispatch('toast', { type: 'error', message: 'Kopiëren lukte niet' }))">
            <x-icon name="copy" class="size-3.5" />
            Opdracht-code kopiëren
        </x-button>
    </x-card>
</div>
