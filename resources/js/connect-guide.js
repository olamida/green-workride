import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

function haversine(a, b) {
    const R = 6371000;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(b.lat - a.lat);
    const dLng = toRad(b.lng - a.lng);
    const s =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(s));
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Passenger-to-vehicle connect guide.
 *
 * Privacy rule: the map only ever shows THIS passenger's position and the
 * shared trip target (vehicle or boarding waypoint) — nothing else. Live
 * coordinates arrive only over the private `trip.{id}` channel, which Laravel
 * authorizes per-participant in routes/channels.php.
 */
export function initConnectGuide(element, config, target) {
    if (!element) {
        return null;
    }

    const map = L.map(element, {
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const targetMarker = L.marker([target.lat, target.lng]).addTo(map);
    targetMarker.bindPopup(`<strong>${target.label}</strong>`);
    map.setView([target.lat, target.lng], config.zoom_overview);

    let passengerMarker = null;
    let routeLayer = null;
    let lastPassenger = null;
    let distanceM = null;
    let durationS = null;

    const banner = document.querySelector('[data-guide-banner]');
    const distanceEl = document.querySelector('[data-guide-distance]');
    const etaEl = document.querySelector('[data-guide-eta]');
    const statusEl = document.querySelector('[data-guide-status]');

    const setStatus = (status) => {
        if (statusEl) {
            statusEl.textContent = status;
            statusEl.setAttribute('aria-live', 'polite');
        }
    };

    const updateBanner = (route) => {
        distanceM = route.distance_m;
        durationS = route.duration_s;

        const mins = Math.ceil(durationS / 60);
        if (distanceEl) {
            distanceEl.textContent = `${Math.round(distanceM)} m`;
        }
        if (etaEl) {
            etaEl.textContent = `~${mins} min walk`;
        }

        if (distanceM <= config.arrived_radius_m) {
            setStatus('You have arrived — the vehicle is within the pick-up radius.');
            map.panTo([target.lat, target.lng], { animate: !prefersReducedMotion() });
        }
    };

    const drawRoute = (points, provider) => {
        if (routeLayer) {
            map.removeLayer(routeLayer);
        }
        routeLayer = L.polyline(points, {
            color: '#2E7D32',
            weight: 4,
            dashArray: '6 8',
            lineCap: 'round',
        }).addTo(map);
        if (routeLayer.getBounds && routeLayer.getBounds().isValid()) {
            map.fitBounds(routeLayer.getBounds(), { padding: [48, 48], animate: !prefersReducedMotion() });
        }
        setStatus(`Walking route ready${provider && provider !== 'osrm' ? ` · ${provider}` : ''}.`);
    };

    const fetchRoute = async (pos) => {
        const url = `${config.route_url}?from_lat=${pos.lat.toFixed(6)}&from_lng=${pos.lng.toFixed(6)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            throw new Error('route unavailable');
        }
        return res.json();
    };

    const updatePassenger = async (pos) => {
        if (!passengerMarker) {
            passengerMarker = L.marker([pos.lat, pos.lng], {
                title: 'You',
            }).addTo(map);
            passengerMarker.bindPopup('<strong>You</strong>');
        } else {
            passengerMarker.setLatLng([pos.lat, pos.lng]);
        }

        if (
            lastPassenger &&
            haversine(lastPassenger, pos) < config.re_route_threshold_m
        ) {
            return;
        }
        lastPassenger = pos;

        try {
            const route = await fetchRoute(pos);
            updateBanner(route);
            drawRoute(route.points, route.provider);
        } catch {
            // Routing provider unreachable — show the straight-line estimate.
            const straight = {
                distance_m: haversine(pos, target),
                duration_s: Math.round(haversine(pos, target) / ((config.walking_speed_kmh / 3.6) || 1.39)),
                points: [
                    [pos.lat, pos.lng],
                    [target.lat, target.lng],
                ],
                provider: 'straight-line estimate',
            };
            updateBanner(straight);
            drawRoute(straight.points, straight.provider);
        }
    };

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            (pos) => {
                updatePassenger({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                });
            },
            () => {
                setStatus('Location unavailable — showing the boarding point. Check the map or the driver chat.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    }

    const followTarget = (lat, lng) => {
        targetMarker.setLatLng([lat, lng]);
        map.panTo([lat, lng], { animate: !prefersReducedMotion() });
    };

    if (window.Echo) {
        window.Echo.private(`trip.${config.trip_id}`)
            .listen('.TripLocationUpdated', (e) => {
                followTarget(e.current_lat, e.current_lng);
                setStatus('Vehicle position updated.');
            })
            .listen('.TripCancelled', () => {
                setStatus('This ride has been cancelled by the driver.');
            })
            .listen('.TripCompleted', () => {
                setStatus('This ride has been completed — well done.');
            })
            .listen('.BookingCancelled', (e) => {
                if (e.booking_id && Number(e.booking_id) === Number(config.my_booking_id)) {
                    setStatus('Your booking was cancelled — this guide is no longer active.');
                }
            });
    }

    return map;
}

if (typeof window !== 'undefined') {
    window.initConnectGuide = initConnectGuide;
}
