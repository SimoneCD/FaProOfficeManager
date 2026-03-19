// Service Worker registrieren
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/js/sw.js');
            console.log('Service Worker registriert:', registration);

            // Push-Subscription starten
            await subscribeToPush(registration);
        } catch (error) {
            console.error('Service Worker Fehler:', error);
        }
    });
}

// VAPID-Schlüssel aus Meta-Tag holen (oder direkt hier einfügen)
const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]').content || 'BPg0H4V3YBXsR45SDpJH1jfZ5D6IAlODeGimuLKCj-acReC_MVUD2rx6jsogXMrS9iuhUyFY4_tSNmH6j11oT7g';

// URL-Base64 zu Uint8Array konvertieren (für VAPID)
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Push-Subscription anfordern
async function subscribeToPush(registration) {
    try {
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });
            console.log('Neue Subscription erstellt:', subscription);
        } else {
            console.log('Bestehende Subscription:', subscription);
        }
        const response = await fetch('/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
        const result = await response.json();
        console.log('Subscription gesendet:', result);

    } catch (error) {
        console.error('Push-Subscription fehlgeschlagen:', error);
    }
}

// // Button für manuelles Abonnieren (optional)
// document.getElementById('subscribeButton')?.addEventListener('click', () => {
//     navigator.serviceWorker.ready.then(subscribeToPush);
// });
//document.getElementById('subscribeButton').addEventListener('click', subscribeToPush);

// // Button für manuelles Abonnieren (optional)
// document.getElementById('subscribeButton')?.addEventListener('click', () => {
//     navigator.serviceWorker.ready.then(subscribeToPush);
// });
//document.getElementById('subscribeButton').addEventListener('click', subscribeToPush);