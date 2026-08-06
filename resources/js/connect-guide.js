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
 * Branded map pin — circular head (forest for the vehicle, blue for "you"),
 * white ring and a small gold badge. The head carries a one-shot soft pulse
 * when it appears or moves so motion stays purposeful (feedback, not a beat).
 */
function createPin(color, badge) {
    const dot = badge
        ? `<span class="wr-pin-badge">${badge}</span>`
        : '<span class="wr-pin-dot"></span>';

    return L.divIcon({
        className: 'wr-pin-icon',
        html: `<div class="wr-pin" style="--pin: ${color}"><div class="wr-pin-body">${dot}</div></div>`,
        iconSize: [34, 40],
        iconAnchor: [17, 40],
        popupAnchor: [0, -36],
    });
}

const quietPan = () => ({ animate: !prefersReducedMotion() });

/**
 * Passenger-to-vehicle connect guide.
 *
 * Privacy rule: the map only ever shows THIS passenger's position and the
 * shared trip target (vehicle or boarding waypoint) — nothing else. Live
 * coordinates arrive only over the private `trip.{id}` channel, which Laravel
 * authorizes per-participant in routes/channels.php.
 *
 * UI contract: the function is stateless about presentation — it reports
 * distance/ETA, status copy and terminal states through the `callbacks` the
 * Alpine shell passes in. The shell owns the glass HUD, the number ticks and
 * the arrived/missed panels.
 */
export function initConnectGuide(element, config, target, callbacks = {}) {
    if (!element) {
        return null;
    }

    const onStatus = callbacks.onStatus || (() => {});
    const onDistance = callbacks.onDistance || (() => {});
    const onArrived = callbacks.onArrived || (() => {});
    const onMissed = callbacks.onMissed || (() => {});
    const onVoice = callbacks.onVoice || (() => {});

    if (target.lat === null || target.lng === null) {
        element.innerHTML =
            '<div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-ink-300 bg-paper/60 p-6 text-center text-sm text-ink-600">' +
            'No boarding point shared yet — the driver has not pinned a location for this ride.</div>';
        return null;
    }

    const reduce = prefersReducedMotion();
    const map = L.map(element, {
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const targetMarker = L.marker([target.lat, target.lng], {
        icon: createPin('#2e7d32', 'B'),
        title: target.label,
    }).addTo(map);
    targetMarker.bindPopup(`<strong>${target.label}</strong>`);
    map.setView([target.lat, target.lng], config.zoom_overview);

    let state = 'overview'; // overview → active → arrived | missed
    let passengerMarker = null;
    let routeLayer = null;
    let lastPassenger = null;
    let lastAnnouncedM = null; // voice milestone — re-announce every ~100 m

    const format = (distanceM, durationS) => ({
        distance: `${Math.round(distanceM)} m`,
        eta: `~${Math.ceil(durationS / 60)} min walk`,
    });

    // Optional voice layer (guide §5.3) — a quiet "still X metres away" nudge
    // every 100 m, plus arrivals/terminal states. The Alpine shell gates this
    // on an explicit user toggle; never on by default.
    const announceDistance = (distanceM) => {
        const rounded = Math.round(distanceM);
        if (lastAnnouncedM === null || lastAnnouncedM - rounded >= 100) {
            lastAnnouncedM = rounded;
            onVoice(`${rounded} metres away — the vehicle is approaching.`);
        }
    };

    const ensureRouteVisible = () => {
        if (!routeLayer || !routeLayer.getBounds || !routeLayer.getBounds().isValid()) {
            return;
        }
        const bounds = routeLayer.getBounds();
        if (!bounds.contains(passengerMarker.getLatLng())) {
            map.fitBounds(bounds, { padding: [48, 48], ...quietPan() });
        }
    };

    const updateBanner = (route) => {
        if (state !== 'active') {
            return;
        }
        onDistance(format(route.distance_m, route.duration_s));
        announceDistance(route.distance_m);

        if (route.distance_m <= config.arrived_radius_m) {
            state = 'arrived';
            onArrived();
            onVoice('You have arrived. Wave to the driver.');
            map.panTo([target.lat, target.lng], quietPan());
        }
    };

    const drawRoute = (points, provider) => {
        if (routeLayer) {
            map.removeLayer(routeLayer);
        }
        routeLayer = L.polyline(points, {
            color: '#2E7D32',
            weight: 4,
            lineCap: 'round',
            opacity: 0.95,
            className: 'wr-route-line',
        }).addTo(map);
        ensureRouteVisible();
        onStatus(`Walking route ready${provider && provider !== 'osrm' ? ` · ${provider}` : ''}.`);
    };

    const fetchRoute = async (pos) => {
        const url = `${config.route_url}?from_lat=${pos.lat.toFixed(6)}&from_lng=${pos.lng.toFixed(6)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            throw new Error('route unavailable');
        }
        return res.json();
    };

    const pulsePin = (marker) => {
        const body = marker.getElement()?.querySelector('.wr-pin-body');
        if (!body) {
            return;
        }
        body.classList.remove('wr-pin-soft', 'wr-pin-move');
        void body.offsetWidth;
        body.classList.add('wr-pin-soft');
    };

    const updatePassenger = async (pos) => {
        if (!passengerMarker) {
            passengerMarker = L.marker([pos.lat, pos.lng], {
                icon: createPin('#2563eb'),
                title: 'You',
            }).addTo(map);
            passengerMarker.bindPopup('<strong>You</strong>');
            pulsePin(passengerMarker);
        } else {
            passengerMarker.setLatLng([pos.lat, pos.lng]);
        }

        if (state === 'overview') {
            // Quiet estimate while the guide is still in overview — no fetch,
            // no camera movement, no polylines. The number ticks when it updates.
            const distanceM = haversine(pos, target);
            const durationS = Math.round(distanceM / ((config.walking_speed_kmh / 3.6) || 1.39));
            onDistance(format(distanceM, durationS));
            return;
        }

        if (lastPassenger && haversine(lastPassenger, pos) < config.re_route_threshold_m) {
            return;
        }
        lastPassenger = pos;

        try {
            const route = await fetchRoute(pos);
            updateBanner(route);
            drawRoute(route.points, route.provider);
        } catch {
            // Routing provider unreachable — show the straight-line estimate.
            const distanceM = haversine(pos, target);
            const straight = {
                distance_m: distanceM,
                duration_s: Math.round(distanceM / ((config.walking_speed_kmh / 3.6) || 1.39)),
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

    const startFollow = () => {
        if (state !== 'overview') {
            return;
        }
        state = 'active';

        if (passengerMarker) {
            map.fitBounds(
                L.latLngBounds([passengerMarker.getLatLng(), targetMarker.getLatLng()]),
                { padding: [48, 48], maxZoom: config.zoom_follow, ...quietPan() }
            );
        } else {
            map.setView([target.lat, target.lng], config.zoom_follow);
        }

        if (lastPassenger) {
            updatePassenger(lastPassenger);
        }
        onStatus('Walking to the green dot — follow the route.');
        onVoice('Following the route to the green dot.');
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
                onStatus('Location unavailable — showing the boarding point. Check the map or the driver chat.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    }

    const followTarget = (lat, lng) => {
        targetMarker.setLatLng([lat, lng]);
        pulsePin(targetMarker);

        if (state !== 'active') {
            return;
        }
        if (lastPassenger) {
            updatePassenger(lastPassenger);
        }
        ensureRouteVisible();
        onStatus('Vehicle position updated.');
    };

    const miss = (reason) => {
        if (state === 'arrived' || state === 'missed') {
            return;
        }
        state = 'missed';
        onMissed(reason);
        onVoice('The ride is gone. Find another ride.');
    };

    if (window.Echo) {
        window.Echo.private(`trip.${config.trip_id}`)
            .listen('.TripLocationUpdated', (e) => {
                followTarget(e.current_lat, e.current_lng);
            })
            .listen('.TripCancelled', () => {
                miss('The driver cancelled this ride.');
            })
            .listen('.TripCompleted', () => {
                miss('The ride departed — it is already on the road.');
            })
            .listen('.BookingCancelled', (e) => {
                if (e.booking_id && Number(e.booking_id) === Number(config.my_booking_id)) {
                    miss('Your booking was cancelled — this guide is no longer active.');
                }
            });
    }

    return { startFollow };
}

if (typeof window !== 'undefined') {
    window.initConnectGuide = initConnectGuide;
}
