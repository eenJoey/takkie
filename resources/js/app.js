import confetti from 'canvas-confetti';
import { registerPushWorker, takkieNotifications } from './push';

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

// Alpine (meegeleverd door Livewire) componenten
document.addEventListener('alpine:init', () => {
    window.Alpine.data('takkieNotifications', takkieNotifications);
});

// Service worker voor pushmeldingen registreren (zoals vinkie bij elke app-start)
registerPushWorker().catch(() => {});
