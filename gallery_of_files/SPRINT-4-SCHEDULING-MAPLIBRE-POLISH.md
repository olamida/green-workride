# SPRINT 4 PROMPT: Bus Scheduling + MapLibre Upgrade + Final Polish
## AI Coder Prompt — Copy-Paste Ready

> **Sprint:** 4 of 4 — Scheduling & Award-Grade Polish
> **Goal:** World-class bus/ride scheduling like Citymapper/Transit, MapLibre tilted map upgrade, final accessibility + performance pass
> **Base:** input_section.txt §2 Phase 2 + §6 + WORKRIDE-APP-GUIDE §7 GTFS + DEV-GUIDE §6 Fleet/Forecast
> **DoD:** Bus schedules work, MapLibre pitch 35-55° with vector labels, app feels premium on 3G, admin fully mobile-friendly, ready for FCTA demo

### ROLE
You are Senior Scheduling + Map + Performance Engineer. You shipped Transit app scheduling, Citymapper bus times. You know FullCalendar, MapLibre, GTFS, ForecastService.

### TASKS:

#### 4.1 Bus & Ride Scheduling — World-Class Flow

**Research:** Citymapper, Transit app, Google Maps transit, Uber Shuttle, Nairobi Digital Matatus

**Model `app/Models/BusSchedule.php` (CREATE):**
```php
Schema::create('bus_schedules', function($table){
  $table->id();
  $table->foreignId('route_id')->constrained('gtfs_routes');
  $table->foreignId('vehicle_id')->constrained('vehicles');
  $table->foreignId('driver_id')->constrained('users');
  $table->time('departure_time'); // e.g., 06:30
  $table->integer('frequency_minutes')->default(15)->comment('15 peak, 30 off-peak');
  $table->json('days_of_week')->default('["mon","tue","wed","thu","fri"]');
  $table->enum('status', ['active','paused'])->default('active');
  $table->foreignId('workplace_id')->nullable(); // if dedicated to MDA
  $table->timestamps();
});
```

**Service `app/Services/SchedulingService.php` (CREATE):**
- `generateTripsFromSchedule(BusSchedule $schedule, Carbon $date): Collection` — creates actual Trip records for that date using route's waypoints, total_seats from vehicle, fare from PricingService
- `getNextDepartures(string $route_id, int $limit=3): array` — returns next 3 departure times with seats left
- `getFrequencyLabel(BusSchedule $schedule): string` — "Every 15 mins peak, 30 mins off-peak"

**Job `app/Jobs/GenerateRecurringTripsJob.php` (CREATE):**
- Runs daily 5am via scheduler — for each active BusSchedule, generate trips for today + tomorrow
- Idempotent: check if trip already exists for route+departure_time+date via reference `SCHED-{schedule_id}-{date}-{time}`

**For Carpool (on-demand, flexible):**
- Update `Trip` publish form: Add toggle "Repeat" → Mon-Fri checkboxes — if checked, creates multiple Trip records via SchedulingService
- Add toggle "Leave now" vs "Schedule for later" — if now, departure_time = now+15min

**UI:**

1. **Admin Scheduling — `resources/views/admin/scheduling/index.blade.php`:**
   - Use FullCalendar.js (open source) — `npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid`
   - Calendar shows bus schedules as events, color by route (Forest Green for Kubwa-CBD, Blue for Nyanya-Idu etc)
   - Click date → shows trips generated, driver assignment, seats booked
   - Form to create schedule: Route (select GtfsRoute), Vehicle (select), Driver (select), Time (time picker), Frequency (15/30/60), Days (checkboxes)
   - Show demand forecast from `ForecastService::forecastForDate($date, $corridor)` — "Friday 2:30pm after Juma'a — 0.7x demand on CBD, extra buses after 2:30pm on mosque corridors. Saves 30% fuel by not deploying empty buses."

2. **Passenger View — Trip Results:**
   - For bus routes, show: "Bus leaves Berger every 15 mins 6:30-9am. Next 3: 7:15 (4 seats), 7:30 (12 seats), 7:45 (14 seats) — Book seat"
   - For carpool, show: "Live now, 2 seats, leaves in 5 mins"

#### 4.2 MapLibre Upgrade — Award-Grade Tilted Map

**Why MapLibre:** Open-source, free vector tiles, pitch 35-55° for "front-of-road" view like Google Maps, sharp road labels, brandable.

**Installation:**
```bash
npm install maplibre-gl
```

**Feature Flag:** Add `FEATURE_MAPLIBRE` env flag in `config/workride.php` — default false, true for Connect Guide.

**Implementation — `resources/js/map/maplibre.js` (CREATE):**

```js
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

export function initMapLibre(containerId, options) {
  const map = new maplibregl.Map({
    container: containerId,
    style: 'https://demotiles.maplibre.org/style.json', // OR OpenFreeMap: https://api.openfreemap.com/styles/liberty
    // Better: use OpenFreeMap free: https://tiles.openfreemap.org/styles/liberty
    center: options.center || [7.4898, 9.0579], // [lng, lat] for MapLibre
    zoom: options.zoom || 12,
    pitch: options.pitch || 45, // 35-55° for gentle front view
    bearing: options.bearing || 0,
    antialias: true
  });

  map.on('load', () => {
    // Add route line with Forest Green
    map.addSource('route', {type: 'geojson', data: options.routeGeoJson});
    map.addLayer({id: 'route-line', type: 'line', source: 'route', paint: {'line-color': '#0F5132', 'line-width': 5}});

    // Add direction arrows via symbol layer
    // Add junction markers with labels
  });

  return map;
}
```

- Use OpenFreeMap style: `https://tiles.openfreemap.org/styles/liberty` or `https://tiles.openfreemap.org/styles/bright` — no API key, unlimited, perfect for funding story "100% open-source, hosted in Nigeria"
- Enable pitch only on Connect Guide and live trip view — NOT on list view (to save performance)
- Pitch 35-55° — NOT extreme 70-85° on low-end devices — check `if (isLowEndDevice) pitch=0`
- Vector tiles give sharp road labels, better contrast, brandable colors
- Keep Leaflet as fallback: `if (!FEATURE_MAPLIBRE || isLowEnd) use Leaflet`

**Component:** Update `resources/views/components/map/navigation-map.blade.php` to accept `useMapLibre` bool prop — if true, renders MapLibre container, else Leaflet.

- Reuse existing `RoutingService` for geometry — only renderer changes
- Ensure origin "You" and vehicle pins visible, correctly labelled, with direction arrows

#### 4.3 Final Polish — Accessibility + Performance + Mobile

1. **Accessibility:**
   - VoiceOver announces junction changes: `aria-live="polite"` on progress tracker
   - Dynamic Type support via `text-base` + `rem`
   - All interactive elements keyboard accessible, focus ring visible
   - Respects `prefers-reduced-motion` — disable pitch animation and pulse if user prefers reduced motion

2. **Performance on 3G:**
   - Bundle size: Leaflet 40kb, MapLibre 200kb — code-split via `import()` dynamic import for MapLibre only when needed
   - Lazy load map below fold, show skeleton
   - Service worker caches tiles? No — tiles are external, but cache trip data
   - <2s load on 3G — test via Chrome Lighthouse 3G throttling

3. **Admin Mobile Polish:**
   - Verify all tables use responsive Split/Stack
   - Touch targets >=44px everywhere
   - Bottom nav on mobile for 4 most used ops items
   - Same design tokens — Forest Green, glass, Sora/Inter, 16px radius

4. **PWA:**
   - Update `public/manifest.json` icons, ensure Add to Home Screen works
   - Test offline page `/offline`

#### 4.4 Update Docs & Seeding

- Update `DEVELOPMENT-LOG.md` with Sprint 4 changes
- Update seeding to include 15 workplaces + 45 junctions (Nyanya, Zuba, Suleja, Gwagwalada, Lugbe etc) from SPRINT-2 prompt
- Ensure `php artisan migrate:fresh --seed` works, `php artisan gtfs:generate` valid, `npm run build` succeeds

### ACCEPTANCE:
- BusSchedule model works: Admin can create schedule "Kubwa-CBD every 15 mins Mon-Fri 6:30-9am", job generates actual Trips daily 5am, passenger sees "Next 3: 7:15, 7:30, 7:45"
- Carpool recurring: Driver can publish "Repeat Mon-Fri" — creates 5 trips
- MapLibre feature-flagged: When FEATURE_MAPLIBRE=true, Connect Guide shows tilted 45° pitch with vector road labels, Forest Green route, direction arrows, sharp labels — looks better than Google Maps for this specific job
- When FEATURE_MAPLIBRE=false or low-end device, fallback to Leaflet with CartoDB tiles + arrows still works
- Accessibility: VoiceOver announces junction changes, reduced-motion respected, keyboard navigation works
- Performance: <2s load on 3G throttling, bundle split, Lighthouse performance >80
- Admin fully mobile-friendly: sidebar grouped, collapsible, bottom nav on mobile, tables stack, touch targets >=44px, same branding
- All 4 sprints combined: Opening app shows "Where are you going?", destination → join in <4 taps, map never empty, live progress through junctions visible to passenger/driver/admin, timing everywhere, wizards, share-to-join, scheduling
- Tests green, pint clean, phpstan no new errors, build succeeds

### COMMIT:
`feat(nav): sprint 4 bus scheduling + maplibre tilted map + final polish — navigation-first complete`

### FINAL DELIVERABLE — Navigation-First Complete:
- Rider: Where to? → Options → Payment → Connect Guide with live junction progress + timing + tilted map
- Driver: Publish (with recurring) → Live trip with progress + timing
- Admin: Grouped sidebar, mobile-friendly, live view, scheduling calendar, same branding
- Map: Never empty, road labels + arrows, gentle pitch 35-55° on active navigation, OpenFreeMap free vector tiles
- Share: Private link allows colleague to request to join ongoing ride safely

This is award-grade — like Watch Duty (real-time + clarity) + Oko (inclusivity + haptic/audio) + Transit (scheduling) combined for Abuja's informal junctions.

Execute step-by-step. Prefer clarity and real-world performance on Abuja 3G devices.
