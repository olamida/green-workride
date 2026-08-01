import './bootstrap';
import Alpine from 'alpinejs';
import tripChat from './trip-chat';
import tripLive from './trip-live';

window.Alpine = Alpine;

Alpine.data('tripChat', tripChat);
Alpine.data('tripLive', tripLive);

Alpine.start();
