// Pushmeldingen in de browser (port van vinkie src/lib/push.ts).
// De server krijgt alleen het abonnement; versturen gebeurt door `php artisan reminders:send`.

export function pushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);

    return Uint8Array.from(raw, (char) => char.charCodeAt(0));
}

export async function registerPushWorker() {
    if (!pushSupported()) {
        return null;
    }

    return navigator.serviceWorker.register('/sw.js');
}

export async function getExistingSubscription() {
    if (!pushSupported()) {
        return null;
    }

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');

    return registration ? registration.pushManager.getSubscription() : null;
}

export async function subscribe(vapidPublicKey) {
    if (!pushSupported()) {
        throw new Error('Meldingen worden niet ondersteund op dit apparaat.');
    }

    if (!vapidPublicKey) {
        throw new Error('Meldingen zijn nog niet ingesteld op de server.');
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        throw new Error('Meldingen zijn geblokkeerd in je browser.');
    }

    const registration = await registerPushWorker();
    await navigator.serviceWorker.ready;

    return (
        (await registration.pushManager.getSubscription()) ??
        (await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        }))
    );
}

export function contentEncoding() {
    return (PushManager.supportedContentEncodings ?? ['aesgcm'])[0] ?? 'aes128gcm';
}

export function timezone() {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Europe/Amsterdam';
}

/** Alpine-component voor de kaart "Meldingen" op /profiel. */
export function takkieNotifications({ vapidPublicKey }) {
    return {
        supported: pushSupported(),
        busy: false,
        deviceOn: false,

        async init() {
            this.deviceOn = !!(await getExistingSubscription());
        },

        timezone,

        async toggle() {
            this.busy = true;

            try {
                if (this.$wire.enabled) {
                    await this.$wire.disable(timezone());
                } else {
                    const subscription = await subscribe(vapidPublicKey);
                    await this.$wire.enable(subscription.toJSON(), contentEncoding(), timezone());
                    this.deviceOn = true;
                }
            } catch (error) {
                window.dispatchEvent(
                    new CustomEvent('toast', {
                        detail: { type: 'error', message: error?.message ?? 'Meldingen konden niet worden aangezet' },
                    }),
                );
            } finally {
                this.busy = false;
            }
        },

        async turnOffDevice() {
            this.busy = true;

            try {
                const subscription = await getExistingSubscription();

                if (subscription) {
                    await this.$wire.disableDevice(subscription.endpoint);
                    await subscription.unsubscribe();
                }

                this.deviceOn = false;
            } finally {
                this.busy = false;
            }
        },
    };
}
