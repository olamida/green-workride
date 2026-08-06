import './bootstrap';
import Alpine from 'alpinejs';
import tripChat from './trip-chat';
import tripLive from './trip-live';
import useRoadSensor from './use-road-sensor';
import commandPalette from './command-palette';
import boardLive from './board-live';
import connectGuideUI from './connect-guide-ui';
import paymentPicker from './payment-picker';

window.Alpine = Alpine;

Alpine.data('tripChat', tripChat);
Alpine.data('tripLive', tripLive);
Alpine.data('roadSensor', useRoadSensor);
Alpine.data('commandPalette', commandPalette);
Alpine.data('boardLive', boardLive);
Alpine.data('connectGuideUI', connectGuideUI);
Alpine.data('paymentPicker', paymentPicker);

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

// Install-app Alpine component — used in the profile menu + mobile "More" sheet.
Alpine.data('installApp', () => ({
    canInstall: false,
    init() {
        this.canInstall = Boolean(window.deferredInstallPrompt);
        window.addEventListener('wr-install-ready', () => {
            this.canInstall = true;
        });
        window.addEventListener('appinstalled', () => {
            this.canInstall = false;
            window.deferredInstallPrompt = null;
        });
    },
    install() {
        const prompt = window.deferredInstallPrompt;
        if (! prompt) return;
        prompt.prompt();
        prompt.userChoice.finally(() => {
            window.deferredInstallPrompt = null;
            this.canInstall = false;
        });
    },
}));

// Mobile bottom nav — the More sheet + install entry (shares installApp state).
Alpine.data('mobileNav', () => ({
    more: false,
    canInstall: false,
    init() {
        this.canInstall = Boolean(window.deferredInstallPrompt);
        window.addEventListener('wr-install-ready', () => {
            this.canInstall = true;
        });
        window.addEventListener('appinstalled', () => {
            this.canInstall = false;
            window.deferredInstallPrompt = null;
        });
    },
    install() {
        this.more = false;
        const prompt = window.deferredInstallPrompt;
        if (! prompt) return;
        prompt.prompt();
        prompt.userChoice.finally(() => {
            window.deferredInstallPrompt = null;
            this.canInstall = false;
        });
    },
}));
