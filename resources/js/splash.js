// Laadscherm (zie resources/views/components/splash.blade.php).
// Bij het openen: de splash verschijnt pas na 0,3 s (CSS) en verdwijnt zodra de pagina geladen is.
// Bij wisselen van pagina (wire:navigate): opnieuw pas tonen als het langer dan 0,3 s duurt.

function splash() {
    return document.getElementById('takkie-splash');
}

function hide() {
    splash()?.classList.add('is-hidden');
}

function showDelayed() {
    const el = splash();

    if (!el) {
        return;
    }

    // animatie herstarten, zodat de vertraging van 0,3 s opnieuw telt
    el.classList.add('is-hidden');
    void el.offsetWidth;
    el.classList.remove('is-hidden');
}

if (document.readyState === 'complete') {
    hide();
} else {
    window.addEventListener('load', hide, { once: true });
}

document.addEventListener('livewire:navigate', showDelayed);
document.addEventListener('livewire:navigated', hide);

// terug-knop / bfcache: nooit met een zichtbare splash terugkomen
window.addEventListener('pageshow', hide);
