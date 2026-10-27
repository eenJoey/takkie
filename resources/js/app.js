import confetti from 'canvas-confetti';
import { registerPushWorker, takkieNotifications } from './push';
import './splash';

// Confetti bij het afvinken van een taak, met dezelfde instellingen als vinkie
window.addEventListener('task-completed', () => {
    confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.7 },
        colors: ['#f59e0b', '#10b981', '#ec4899', '#6366f1', '#fbbf24'],
        scalar: 0.9,
    });
});

// Als app op het beginscherm (PWA): laat de server dat weten, dan blijf je daar ingelogd (zie KeepSignedIn)
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    document.cookie = `takkie_pwa=1; max-age=${60 * 60 * 24 * 400}; path=/; samesite=lax${location.protocol === 'https:' ? '; secure' : ''}`;
}

// Sessie verlopen (419)? Niet de Engelse "page expired"-vraag, maar stil herladen: het onthoud-cookie logt je weer in
document.addEventListener('livewire:init', () => {
    window.Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status === 419) {
                preventDefault();
                window.location.reload();
            }
        });
    });
});

// Alpine (meegeleverd door Livewire) componenten
document.addEventListener('alpine:init', () => {
    window.Alpine.data('takkieNotifications', takkieNotifications);
});

// Service worker voor pushmeldingen registreren (zoals vinkie bij elke app-start)
registerPushWorker().catch(() => {});
