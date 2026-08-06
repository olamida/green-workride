import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

/**
 * Map-first trip board: pins every trip at its live position (active rides) or
 * its corridor anchor (scheduled rides). Clicking a pin opens the trip.
 *
 * The list below the map remains the primary keyboard/AT path — the map is a
 * visual overlay, never a hard gate. Markers are updated by the live
 * seat-counter channel via `updateTripSeats`.
 */
export function initTripsMap(element, trips, config) {
    if (!element) {
        return null;
    }

    const bounds = [];

    const map = L.map(element, {
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const markerColor = (trip) => {
        if (trip.status === 'active') return '#2E7D32';
        if (trip.is_free_volunteer) return '#FBC02D';
        return '#0F172A';
    };

    const markers = new Map();

    trips.forEach((trip) => {
        const label = [
            trip.route_name,
            trip.leaving_soon ? 'Leaving soon' : `Departs ${trip.departure_time}`,
            `${trip.available_seats}/${trip.total_seats} seats`,
            trip.is_free_volunteer ? 'FREE volunteer' : `₦${trip.fare}`,
        ]
            .filter(Boolean)
            .join(' · ');

        const marker = L.circleMarker([trip.lat, trip.lng], {
            radius: trip.status === 'active' ? 9 : 7,
            color: '#ffffff',
            weight: 2,
            fillColor: markerColor(trip),
            fillOpacity: 0.9,
            title: trip.route_name,
        })
            .addTo(map)
            .bindTooltip(label, { direction: 'top', offset: [0, -10] })
            .on('click', () => {
                window.location.href = trip.url;
            });

        markers.set(String(trip.id), marker);
        bounds.push([trip.lat, trip.lng]);
    });

    if (bounds.length) {
        map.fitBounds(L.latLngBounds(bounds), { padding: [40, 40], animate: false });
    } else {
        const cbd = config.cbd;
        map.setView([cbd.lat, cbd.lng], 12);
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
        updateTripSeats(tripId, available, total) {
            const marker = markers.get(String(tripId));
            if (!marker) return;
            const isFull = available < 1;
            marker.setRadius(isFull ? 5 : 7);
            marker.setStyle({ fillColor: isFull ? '#9CA3AF' : markerColor({ status: 'active' }) });
            marker.setTooltipContent(`${marker.getTooltip().getContent().split('·')[0]} · ${available}/${total} seats`);
            if (reducedMotion) return;
            marker.openTooltip();
            setTimeout(() => marker.closeTooltip(), 1800);
        },
        map,
    };
}

if (typeof window !== 'undefined') {
    window.initTripsMap = initTripsMap;
}
