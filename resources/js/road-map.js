import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

export function initRoadMap(element, events) {
    if (!element) {
        return;
    }

    const map = L.map(element).setView([9.06, 7.43], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const severityColor = (severity) => {
        if (severity >= 4) return '#dc2626';
        if (severity === 3) return '#f59e0b';
        return '#16a34a';
    };

    events.forEach((event) => {
        const color = severityColor(event.severity);

        L.circleMarker([event.lat, event.lng], {
            radius: 6,
            color: color,
            fillColor: color,
            fillOpacity: 0.7,
            weight: 1,
        })
            .addTo(map)
            .bindPopup(`<strong>${event.road_name ?? event.type}</strong><br>Severity: ${event.severity}/5`);
    });

    return map;
}

if (typeof window !== 'undefined') {
    window.initRoadMap = initRoadMap;
}
