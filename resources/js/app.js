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
