import 'leaflet/dist/leaflet.css';
import { L, createMap, fitOrCenter, addRouteLine, corridorAnchor } from '../map/common.js';

/**
 * Navigation-first home map.
 *
 * Renders every trip pin (live at coords, scheduled at corridor anchors),
 * corridor demand labels, and when the rider searches / picks a destination it
 * focuses the map and draws a "go there" route to the corridor terminal.
 *
 * The trip list below remains the primary path — the map is a visual overlay.
 */
export function initNavigationMap(element, trips, config) {
    if (!element) {
        return null;
    }

    const map = createMap(element, config);
    if (!map) {
        return null;
    }

    const pins = new Map();
    const bounds = [];
    const colorFor = (trip) => {
        if (trip.status === 'active') return '#2E7D32';
        if (trip.is_free_volunteer) return '#FBC02D';
        return '#0F172A';
    };

    trips.forEach((trip) => {
        const anchor = trip.lat && trip.lng
            ? [trip.lat, trip.lng]
            : corridorAnchor(trip.corridor, config);
        const lat = Number(anchor[0]);
        const lng = Number(anchor[1]);

        if (![lat, lng].every(Number.isFinite)) {
            return;
        }

        const label = [
            trip.route_name,
            trip.leaving_soon ? 'Leaving soon' : `Departs ${trip.departure_time}`,
            `${trip.available_seats}/${trip.total_seats} seats`,
            trip.is_free_volunteer ? 'FREE volunteer' : `₦${trip.fare}`,
        ].filter(Boolean).join(' · ');

        const marker = L.circleMarker([lat, lng], {
            radius: trip.status === 'active' ? 9 : 7,
            color: '#ffffff',
            weight: 2,
            fillColor: colorFor(trip),
            fillOpacity: 0.9,
            title: trip.route_name,
        })
            .addTo(map)
            .bindTooltip(label, { direction: 'top', offset: [0, -10] })
            .on('click', () => {
                if (trip.url) {
                    window.location.href = trip.url;
                }
            });

        pins.set(String(trip.id), marker);
        bounds.push([lat, lng]);
    });

    // Corridor demand labels ("12 people want this journey" style).
    (config.corridors ?? []).forEach((c) => {
        if (!c.live && !c.demand) {
            return;
        }
        const anchor = corridorAnchor(c.corridor, config);
        L.circleMarker([anchor.lat, anchor.lng], {
            radius: 5,
            color: '#ffffff',
            weight: 1,
            fillColor: c.live ? '#2E7D32' : '#94A3B8',
            fillOpacity: 0.8,
        })
            .addTo(map)
            .bindTooltip(
                `${c.label}${c.demand ? ` · ${c.demand} want this` : ''}${c.live ? ' · live' : ''}`,
                { direction: 'top', offset: [0, -10] }
            );
    });

    fitOrCenter(map, bounds, config);

    let routeLine = null;
    let destPin = null;

    return {
        map,
        focusDestination(result) {
            if (destPin) {
                map.removeLayer(destPin);
                destPin = null;
            }
            if (routeLine) {
                map.removeLayer(routeLine);
                routeLine = null;
            }

            const lat = Number(result.lat);
            const lng = Number(result.lng);
            if (![lat, lng].every(Number.isFinite)) {
                return;
            }

            destPin = L.marker([lat, lng], {
                title: result.name,
            }).addTo(map).bindTooltip(result.name, { direction: 'top', offset: [0, -10] });

            map.fitBounds(L.latLngBounds([[lat, lng]]), { padding: [60, 60], animate: false });
            map.setZoom(Math.max(map.getZoom(), 13));
        },
    };
}

if (typeof window !== 'undefined') {
    window.initNavigationMap = initNavigationMap;
}
