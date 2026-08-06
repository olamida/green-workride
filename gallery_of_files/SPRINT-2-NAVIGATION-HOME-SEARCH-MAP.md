# SPRINT 2 PROMPT: Navigation-First Home + Search + Map Fixes
## AI Coder Prompt — Copy-Paste Ready

> **Sprint:** 2 of 4 — Navigation-First
> **Goal:** First screen is "Where are you going?" — search destination → see rides going your way → join. Fix empty-zoom map problem. Implement share-to-join link.
> **Base:** input_section.txt §1,2 + WORKRIDE-APP-GUIDE §2,3 + 45 junctions from seeding prompt
> **DoD:** Opening app shows search, user can go destination → join in <4 taps, map never empty

### ROLE
You are Senior Product + Map Engineer. You shipped Citymapper, Transit app search. You know WorkRide TripMatchingService (Haversine 2km) + GeofenceService + RoutingService OSRM chain.

### TASKS:

#### 2.1 Navigation Backend — `app/Http/Controllers/Api/V1/NavigationController.php` (CREATE)

```php
class NavigationController {
  public function search(Request $r): JsonResponse // q, lat, lng
    // 1. Search junctions + workplaces table where name LIKE %q% + state
    // 2. If <3 results, fallback to RoutingService::geocode(q) (OSRM)
    // 3. Return [{id, name, lat, lng, type: junction|workplace|geocode, corridor, passenger_volume_daily}]

  public function directions(Request $r): JsonResponse // from_lat,lng to_lat,lng
    // 1. Use RoutingService to get route geometry
    // 2. Use TripMatchingService::findTripsNear(from, to, timeWindow) — your existing Haversine + corridor logic
    // 3. Return {route: {geometry, distance_km, duration_min}, trips: [TripResource with seats, fare, driver, eta, co2], demand: DemandService pending check-ins near destination}

  public function nearby(Request $r): JsonResponse // lat,lng radius=2km
    // Return live trips within radius for map
}
```

- Routes: `GET /api/v1/navigation/search`, `directions`, `nearby` in `routes/api.php`
- Add `app/Services/NavigationService.php` that orchestrates TripMatching + Geofence + Routing + Demand
- Ensure existing money/verification gates intact

#### 2.2 Rider Home — `resources/views/home.blade.php` or `resources/views/trips/index.blade.php` (REWRITE)

New structure (mobile-first):

```blade
<x-layouts.app>
  <div class="max-w-[480px] mx-auto">
    <!-- Header -->
    <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-xl p-4">
      <div class="flex justify-between"><x-brand.logo /> <bell + avatar></div>
    </header>

    <!-- Where to? Search — BIG -->
    <div class="p-4">
      <div x-data="whereTo()" class="relative">
        <input x-model="query" @input.debounce.300ms="search()" placeholder="Where are you going?" class="w-full h-14 rounded-full bg-[var(--color-surface)] pl-12 pr-12 text-base shadow-sm" />
        <button class="absolute right-2 top-2">🎤</button>
        <div x-show="results.length" class="absolute mt-2 w-full bg-white rounded-[16px] shadow-lg z-30">
          <template x-for="r in results"><div @click="select(r)"><span x-text="r.name"></span> <span x-text="r.corridor"></span></div></template>
        </div>
      </div>
    </div>

    <!-- Corridor Chips horizontal scroll -->
    <div class="flex gap-2 overflow-x-auto px-4 pb-2 scrollbar-hide">
      <button class="chip active">All <span class="live-dot"></span></button>
      <button>Kubwa→CBD <span class="badge">12 leaving</span></button>
      <button>Nyanya→Idu <span class="badge">8 leaving</span></button>
      <button>Lugbe→CBD <span class="badge">5 leaving</span></button>
      <button>Garki→Wuse</button>
    </div>

    <!-- Map 60% height -->
    <div id="nav-map" class="h-[55vh] mx-4 rounded-[16px] overflow-hidden relative"></div>

    <!-- Bottom Sheet -->
    <div class="mt-4 bg-white rounded-t-[24px] shadow-[0_-4px_20px_rgba(0,0,0,0.08)] p-4">
      <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-3"></div>
      <h3 class="font-heading font-semibold">Nearby Rides Going Your Way</h3>
      <div id="trip-list"><!-- Trip cards --></div>
    </div>
  </div>
</x-layouts.app>
```

- Trip card: driver avatar + verification badge + route name "Zuba → Berger → Secretariat" + seats + fare ₦800 + ETA + CO2 + "Join" button Yellow accent
- Empty state: If no trips → Show "No rides yet. Be first to publish? [Publish Trip] or [Check-in Demand: I'm at Berger, need CBD]"

#### 2.3 Map Fixes — `resources/js/map/navigation.js` (CREATE/UPDATE)

Current problem: zoom to location shows almost no content.

Fixes:

```js
// Use better tiles with labels
const tiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {maxZoom: 19});

// Always fitBounds with padding, never setView to single point
function focusOnRoute(origin, destination, routeGeometry) {
  const bounds = L.latLngBounds([origin, destination]);
  if(routeGeometry) bounds.extend(routeGeometry);
  map.fitBounds(bounds, {padding: [40, 80], maxZoom: 15, animate: true, duration: 1.5});
}

// Add direction arrows
import 'leaflet-polylinedecorator';
const decorator = L.polylineDecorator(polyline, {
  patterns: [{offset: '10%', repeat: 100, symbol: L.Symbol.arrowHead({pixelSize: 12, pathOptions: {color: '#0F5132'}})}]
}).addTo(map);

// Ensure origin and vehicle pins visible and labelled
L.marker(origin, {icon: youIcon}).bindTooltip("You", {permanent: true, direction: 'top'}).addTo(map);

// Min/max bounds for Abuja
map.setMinZoom(10);
map.setMaxBounds([[8.5, 7.0], [9.5, 8.0]]); // FCT + environs

// On destination select → flyTo with full route in view
map.flyTo([lat,lng], 15, {duration: 1.5});
```

- Create component `resources/views/components/map/navigation-map.blade.php` props: center, zoom, waypoints, trips, showArrows
- Show 500m geofence circle around junctions on search select
- Show passenger_volume_daily: "1,500+ daily" under junction name

#### 2.4 Share-to-Join Link

- `Trip` model: Add `share_code` unique 6-char (e.g., `Str::upper(Str::random(6))`)
- `TripController@share($trip)` → public view `resources/views/trips/share.blade.php` — shows trip summary, live location, "Join this ride" button + QR code
- Route: `GET /trips/{trip}/share` (public, guest-safe uses `layouts/public`) + `GET /trips/{trip}/share?ref={userId}` tracks referral
- When passenger joins via share link, create booking with `referred_by = ref` + activity log
- Copy link button with Web Share API fallback

#### 2.5 Alpine Search Component — `resources/js/navigation/search.js`

```js
Alpine.data('whereTo', () => ({
  query: '', results: [], selected: null,
  async search() {
    if(this.query.length < 2) return;
    const res = await fetch(`/api/v1/navigation/search?q=${this.query}&lat=${userLat}&lng=${userLng}`);
    this.results = await res.json();
  },
  select(r) {
    this.selected = r;
    window.dispatchEvent(new CustomEvent('destination-selected', {detail: r}));
  }
}))
```

- Listen for `destination-selected` in map component to trigger directions + trip fetch

### ACCEPTANCE:
- Opening app (rider) immediately presents large "Where are you going?" search — not corridor filter
- Typing "Nyanya" shows Nyanya Under-Bridge + Mararaba + Masaka with passenger_volume
- Selecting destination → map fitBounds shows origin + destination + route with road labels + arrows, never empty
- Trip list shows rides going that way sorted by departure soonest
- Share link `/trips/{id}/share` works public, join via link creates booking with referral
- Map has CartoDB Positron tiles with labels, arrows on polyline, 500m geofence visible
- Tests: NavigationController search returns junctions, directions returns trips

### COMMIT:
`feat(nav): sprint 2 navigation-first home + search + map fixes + share link`
