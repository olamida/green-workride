import L from 'leaflet';
import 'leaflet-polylinedecorator';

/**
 * Shared Leaflet helpers for WorkRide maps (navigation-first sprint 1).
 *
 * Every map (trip board, road intelligence, connect guide, navigation home)
 * reads the same FCT bounds + corridor anchors from a config object so the
 * map is always bounded to Abuja, labelled, and "never empty/meaningless".
 *
 * Each map module passes the same config shape (matching config/workride.php):
 *   {
 *     fct_bounds: { min_lat, max_lat, min_lng, max_lng },
 *     corridor_anchors: { kubwa_cbd: {lat, lng}, ..., cbd: {lat, lng} },
 *   }
 */

export function fctBounds(config) {
    const b = config?.fct_bounds ?? {};

    if (![b.min_lat, b.max_lat, b.min_lng, b.max_lng].every(Number.isFinite)) {
        return null;
    }

    return L.latLngBounds([b.min_lat, b.min_lng], [b.max_lat, b.max_lng]);
}

export function maxBounds(config) {
    const bounds = fctBounds(config);

    return bounds ? bounds.pad(0.5) : null;
}

/**
 * Creates a map using CARTO labelled tiles (free, no API key) bounded to the
 * FCT. `config.tiles_url` / `config.min_zoom` / `config.tiles_max_zoom`
 * override the defaults.
 */
export function createMap(element, config = {}) {
    if (!element) {
        return null;
    }

    const map = L.map(element, {
        scrollWheelZoom: false,
        minZoom: config.min_zoom ?? 9,
        maxBounds: maxBounds(config),
    });

    L.tileLayer(
        config.tiles_url ?? 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: config.tiles_max_zoom ?? 19,
        }
    ).addTo(map);

    return map;
}

/**
 * Fits the map to the given [lat, lng] points with padding; falls back to the
 * CBD anchor (from `config.cbd` / `config.corridor_anchors.cbd`) so an empty
 * or remote board never renders a blank world map.
 */
export function fitOrCenter(map, points = [], config = {}) {
    const pts = points.filter((p) => Number.isFinite(p[0]) && Number.isFinite(p[1]));

    if (pts.length) {
        map.fitBounds(L.latLngBounds(pts), { padding: config.padding ?? [40, 40], animate: false });
    } else {
        const anchor = config.cbd ?? { lat: 9.0589, lng: 7.4891 };
        map.setView([anchor.lat, anchor.lng], config.default_zoom ?? 12);
    }

    return map;
}

/**
 * Draws a Forest-Green route polyline with direction arrowheads (progressive
 * enhancement — a missing decorator never breaks the line).
 */
export function addRouteLine(map, latlngs, options = {}) {
    const color = options.color ?? '#2E7D32';

    const line = L.polyline(latlngs, {
        color,
        weight: options.weight ?? 4,
        opacity: options.opacity ?? 0.9,
    }).addTo(map);

    try {
        L.polylineDecorator(line, {
            patterns: [
                {
                    offset: options.arrowOffset ?? '25%',
                    repeat: options.arrowRepeat ?? '20%',
                    symbol: L.Symbol.arrowHead({
                        pixelSize: options.arrowPixelSize ?? 10,
                        polygon: false,
                        pathOptions: { color, weight: 2 },
                    }),
                },
            ],
        }).addTo(map);
    } catch {
        /* arrow decoration is optional */
    }

    return line;
}

/**
 * Resolves a corridor slug to its map anchor (with CBD fallback) so scheduled
 * trips pin at a real place instead of [0, 0].
 */
export function corridorAnchor(corridor, config) {
    const anchors = config?.corridor_anchors ?? {};

    return anchors[corridor] ?? anchors.cbd ?? { lat: 9.0589, lng: 7.4891 };
}

export { L };
