self.addEventListener('install', event => {
    // Überspringe die Wartephase und aktiviere die neue Version sofort
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('push', event => {
    console.log('Push empfangen:', event);
    let data;
    try {
        data = event.data.json();
        console.log('Daten:', data);
    } catch (e) {
        console.error('Fehler beim Parsen der Push-Daten:', e);
        data = { title: 'Fehler', body: 'Push-Daten ungültig' };
    }
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/hd/app/logo/logo_dark_in.png'
    });
});

self.addEventListener('notificationclick', event => {
    console.log('Benachrichtigung geklickt');
    event.notification.close();
    event.waitUntil(clients.openWindow('/'));
});