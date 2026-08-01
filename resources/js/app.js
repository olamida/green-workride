import './bootstrap';
import Alpine from 'alpinejs';
import tripChat from './trip-chat';
import tripLive from './trip-live';
import useRoadSensor from './use-road-sensor';

window.Alpine = Alpine;

Alpine.data('tripChat', tripChat);
Alpine.data('tripLive', tripLive);
Alpine.data('roadSensor', useRoadSensor);

Alpine.start();

// PWA — register the service worker for offline shell + installability.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // SW registration is best-effort (e.g. dev over http://localhost).
        });
    });
}

// Install prompt — surface a non-blocking "Add to Home Screen" call-to-action.
window.deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('wr-install-ready'));
});
