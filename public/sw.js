// Takkie push-meldingen service worker (port van vinkie public/sw.js).
// Alleen voor meldingen — geen caching van de app.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let payload = { title: 'Takkie', body: 'Je hebt taken openstaan.', url: '/dashboard' };

    try {
        if (event.data) {
            const data = event.data.json();
            payload = { ...payload, ...data, url: data.data?.url ?? data.url ?? payload.url };
        }
    } catch {
        if (event.data) {
            payload.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: '/icon-192.png',
            badge: '/icon-192.png',
            tag: payload.tag || 'takkie-reminder',
            data: { url: payload.url || '/dashboard' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        (async () => {
            const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

            for (const client of all) {
                if ('focus' in client) {
                    await client.focus();
                    if ('navigate' in client) {
                        await client.navigate(url);
                    }

                    return;
                }
            }

            await self.clients.openWindow(url);
        })(),
    );
});
