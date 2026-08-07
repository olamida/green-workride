# AI CODER PROMPT: Corridor Service Planning & Live Journey Information
### Scheduling & Demand-Service — BRT-Inspired but WorkRide-Native (NOT BRT Clone)
> **Feature Name:** Corridor Service Planning + Live Journey (Service Planning inside Control Tower, Live Journey on Rider/Driver)
> **Priority:** HIGH — Sprint 5.0 — After Seeding + Time-Bank + Verification — This is your investor moat vs Uber
> **Goal:** Every trip can answer "When will it be at Berger? How long to next junction?" + Demand predicts supply gaps tomorrow 7am + Control Tower sees unserved demand in one screen
> **Principle:** Learn from world-class BRT, but DO NOT become BRT. WorkRide = corridor-based, verified-colleague, mixed-fleet (private cars + staff/programme buses), fixed fares, 48h board + 30-min near-term matcher, atomic seat lock, GTFS/GTFS-RT, IRI road intelligence, Time-Bank, employer programmes

---

## ROLE
You are a Senior Transit Planning Engineer + Laravel Architect + OSRM Specialist + Control Tower Product Designer. You have built service planning for Nairobi Digital Matatus, BRT Lagos, Quick Ride India. You know OSRM is path engine (A* inside it), matching is weighted scorer not A*, and fixed-fare invariants must never break.

## CONTEXT — WHAT WORKRIDE ACTUALLY IS (READ CAREFULLY):

From WORKRIDE-APP-GUIDE v4.0 + DEVELOPMENT-LOG v0.4.0 + Seeding (45 Abuja junctions):

**WorkRide is NOT:**
- Classic BRT operator owning large dedicated fleet on exclusive lanes
- Rigid timetable publisher
- Dynamic pricing surge app

**WorkRide IS:**
- Corridor-based: kubwa_cbd (Kubwa, Zuba, Suleja → Berger → CBD), nyanya_idu (Nyanya, Mararaba, Masaka, Karu → AYA → Idu), lugbe_cbd (Lugbe, Gwagwalada, Kuje → Garki), garki_wuse (Wuse Market → Area 1 → Apo)
- Verified-colleague: Level 1 Staff ID (open), Level 2 NIN (IdentityPass), Level 3 Driver (Smile anti-spoofing)
- Mixed-fleet: private cars (4 seats) + staff/programme buses (14-30 seats) + volunteer free rides
- Fixed fares: 600-1000 per corridor, no surge
- Matching: 48h board + 30-min near-term matcher + atomic seat lock + TripMatchingService weighted scorer (corridor + time window + seats + verification + leaving-soon + soft prefs)
- Already has: GTFS/GTFS-RT generator, IRI road segments Green/Yellow/Red, demand_requests + demand_surveys + OD matrix, Time-Bank ride_credits, wallet 3 balances, Reverb real-time, 45 junctions seeded (Nyanya Under-Bridge 5000 daily, Berger, Kubwa Junction 2000, Lugbe, Zuba, Suleja, Gwagwalada etc)

**What to take from BRT:**
- Predicted arrival windows at named junctions + live progress (not rigid timetable)
- Demand-aware frequency via driver prompting + soft interest for programme buses
- Passenger info: next vehicle, ETA, junction stepper
- Operational control: Control Tower predicted vs actual + alerts

**What NOT to copy:**
- Rigid fixed timetable ignoring 30-min matcher and atomic seat model
- Complex multi-depot vehicle blocking (WorkRide doesn't own fleet)
- Pure A* as primary matching engine — A* is path-finding, OSRM already uses it internally

**Best algorithmic fit:**
- Routing/geometry: Keep OSRM (or Valhalla) — already right tool. A* is internal to OSRM, don't re-implement.
- Matching: Extend existing TripMatchingService weighted scorer
- Demand prediction: Time-series / moving averages on historical bookings + check-ins + day-of-week/seasonality (start simple)
- Service planning: Optimisation turning predicted demand into suggested driver publish times + programme-bus allocations (not full BRT scheduling)

---

## CORE CONCEPTS TO IMPLEMENT:

### 1. Junction-Level Journey Information
For any active/upcoming trip system knows: origin, destination, ordered named junctions/milestones, predicted arrival window at each junction, estimated dwell/meeting time (2 min), running time between junctions, overall duration, live actual vs plan

### 2. Demand Prediction
Short-term 2-48h + medium-term 7-14 days demand by corridor + time band + junction, using demand_requests, bookings, OD matrix, demand_surveys

### 3. Service Response
Prompt drivers to publish at high-demand slots + soft interest for programme buses + empty-state messaging creating supply

### 4. Passenger & Driver Guidance
Clear next vehicle, walking time, junction ETAs, live stepper

### 5. Management Info
Control Tower: predicted vs actual, unserved demand, on-time performance at junctions, vehicle utilisation

---

## STANDARD PROFESSIONAL IMPLEMENTATION PLAN — PHASED:

### Phase 0 — Foundations (Already Partly Present — Verify & Fix)

- Ensure corridor_junctions table exists: id, corridor ENUM(kubwa_cbd,nyanya_idu,lugbe_cbd,garki_wuse), junction_id FK (from your 45 seeded), sequence INT, planned_dwell_seconds INT default 120, geofence_radius INT default 100, is_timing_point bool, created_at
- Seed it: For each corridor, order junctions by real road sequence. Example kubwa_cbd: Zuba (1) → Madalla (2) → Dei-Dei (3) → Kubwa Junction (4) → Dutse (5) → Berger (6) → Wuse Market (7) → Federal Secretariat (8)
- Ensure GTFS stop_times / shapes aligned to these junctions where programme buses exist
- Keep OSRM as routing engine — env OSRM_HOST=http://osrm:5000 — no custom A* implementation
- Feature flag: config/workride.php FEATURE_SERVICE_PLANNING=false, FEATURE_JOURNEY_TIMING=true

### Phase 1 — Journey Information Model (Data + Services) — MOST CRITICAL

**Goal:** Every trip can answer "When will it be at Berger? How long to next junction? Total duration?"

**Migrations:**
- `*_create_corridor_junctions_table.php` — as above
- `*_create_trip_junction_times_table.php`: id, trip_id FK, junction_id FK, sequence, planned_arrival_at datetime, planned_departure_at datetime, actual_arrival_at nullable, actual_departure_at nullable, status pending/arrived/departed/skipped, distance_from_prev_m, duration_from_prev_s, created_at — Index trip_id+sequence unique, junction_id
- `*_add_journey_fields_to_trips_table.php`: Add to trips: total_planned_duration_s INT, total_distance_m INT, journey_planned_at datetime nullable

**Services:**

**A. `app/Services/RoutingService.php` (Extend existing or create):**
- Method `calculateCorridorRoute(origin_junction, destination_junction, corridor)`: Calls OSRM `GET /route/v1/driving/{lon,lat};{lon,lat}?steps=true` — returns distance, duration, geometry polyline
- Method `calculateLegDurations(trip)`: For trip's ordered junctions (from corridor_junctions where trip origin/destination falls between), call OSRM for each leg, store duration/distance. Cache in Redis 24h key `route:{originId}:{destId}`
- Uses OSRM — DO NOT implement A* yourself

**B. `app/Services/JourneyService.php` (New — Core):**
- `plan(trip):` 
  1. Get ordered junctions for trip's corridor between origin and destination (from corridor_junctions)
  2. For each leg, get duration from RoutingService (cached)
  3. Start from trip departure_time, cumulative add dwell (120s) + leg duration → generate planned_arrival_at / planned_departure_at for each junction
  4. Store in trip_junction_times, update trips total_planned_duration_s, total_distance_m, journey_planned_at=now()
  5. Return array junctions + planned times
- `liveProgress(trip):`
  - Get last actual_arrival_at (where actual_arrival_at not null, max sequence)
  - Current junction = that junction, next junction = sequence+1
  - Calculate variance: actual_arrival_at - planned_arrival_at → on-time / 2 min behind
  - Remaining time = sum remaining planned durations
  - Return {current_junction, next_junction, next_eta, variance_seconds, progress_percent, remaining_duration_s, is_delayed bool}
- `recordJunctionArrival(trip, junction, lat/lng)`: Called when vehicle crosses geofence (from Reverb location update). Check distance < geofence_radius (100m), if not yet recorded actual_arrival_at → set actual_arrival_at=now(), broadcast `JunctionArrived` event via Reverb
- All times in Africa/Lagos timezone

**Events:**
- `JunctionArrived` — trip_id, junction_id, planned_at, actual_at, variance — broadcast to `trip.{id}` private channel

**Expose:**

- Rider: Connect Guide `/api/v1/trips/{id}/journey` → returns journey plan
- Rider: My Rides `/api/v1/bookings/{id}/live` → returns liveProgress + journey
- Driver: `/api/v1/driver/trips/{id}/journey` → same + variance

**Deliverable:** Passenger and driver see consistent junction-level timing. Test: Trip from Zuba → Secretariat shows "Berger 07:25-07:27 (2 min dwell), Wuse Market 07:35"

### Phase 2 — Demand Prediction (Simple → Useful)

**Goal:** Predict demand by corridor + time band + origin junction for next 48h and 7-14 days

**Table: `demand_forecasts` (or extend existing forecasts):**
id, corridor ENUM, origin_junction_id FK, time_band VARCHAR (07:00-07:30), forecast_date DATE, predicted_passengers INT, confidence ENUM(low,medium,high), source ENUM(historical,rule_based,ml), created_at — Index corridor+origin_junction_id+forecast_date+time_band unique

**Job: `app/Jobs/GenerateDemandForecastJob.php` — Runs hourly via scheduler:**

Simple transparent rules (NO heavy ML yet):
1. Aggregate last 30 days: bookings + demand_requests + demand_surveys + probe_demand_points, group by corridor, origin_junction, day_of_week (Mon-Sun), hour band (30 min slots 05:00-21:00)
2. Calculate moving average: For each corridor/junction/time_band/day_of_week, predicted = avg(count last 4 same day_of_week) * trend_factor (recent 7 days / previous 7 days)
3. Adjust for seasonality: If today is Monday and last Monday was public holiday, reduce confidence to low
4. Store in demand_forecasts for next 14 days
5. Confidence: high if >20 data points, medium if 5-20, low if <5

**Later optional:** Add simple linear regression via `php-ml` or call Python microservice

**Feature flag:** FEATURE_DEMAND_FORECAST=false initially

**Deliverable:** Control Tower and empty states know "expected 14 passengers at Berger for Kubwa→CBD 07:00-07:30 tomorrow, confidence medium"

### Phase 3 — Service Response & Driver Prompting

**Goal:** Turn predicted demand into actual seats without breaking fixed-fare or seat invariants

**Logic:**

- When forecast or live check-ins (demand_requests pending in last 2h) exceed threshold (predicted_passengers >10 and supply = trips active+scheduled in same time band < predicted/3) and supply low → trigger:
  - Empty-state / Results-screen messaging: In `TripController::search`, if no trips found or <3 trips, return `demand_prompt: {message: "14 people need Kubwa→CBD at 07:15 — Publish a trip", corridor, time_band, predicted_passengers}`
  - Frontend: Show prominent banner "High demand at Berger 07:15 — 14 people need ride — Publish now" with CTA
  - Optional push: `DriverPromptService::notifyDrivers(corridor, time_band)` → FCM to qualified drivers (those who have driven same corridor last 14 days, verification_level 3, not currently driving) — rate limit 1 push per driver per day per corridor to avoid spam

- Programme / staff-bus soft interest: Table `programme_bus_interests`: id, workplace_id, corridor, time_band, date, seats_offered INT, contact_person, status interested/confirmed/cancelled, created_at — Office admins can register interest via Control Tower form "We have 20 seats tomorrow 07:00 Kubwa→CBD" — system treats as potential supply in matching (add to available seats in search, but mark as programme_bus)

- Keep 30-min matcher and atomic seat lock unchanged — prompts only create supply, don't bypass locking

**Optional optimisation:** Simple greedy suggest best publish times: For driver who drives Kubwa→CBD regularly, suggest "Publish at 06:45 for max passengers — predicted 18 people"

**Deliverable:** Demand visibly creates supply. Metric: driver response rate to prompts

### Phase 4 — Live Journey Experience (Rider + Driver)

**Goal:** World-class passenger information

**Rider Views (Blade + Alpine + Leaflet):**

- Connect Guide & My Rides:
  - Walking time to pickup: Use OSRM `foot` profile from user current location to origin junction → "5 min walk to Berger Junction"
  - Time remaining to departure: countdown
  - Next junction + ETA: from JourneyService::liveProgress
  - Overall progress stepper: Horizontal stepper showing all junctions, current highlighted, checkmarks for passed, time labels planned vs actual, variance badge "2 min behind"
  - Share-to-join: Share trip link with live seat counts

- Driver Live View:
  - Same stepper + actual variance "2 min behind at Berger"
  - List of passengers boarding at next junction
  - Next junction navigation via Leaflet with OSRM geometry

- All timing uses same JourneyService so numbers never disagree — single source of truth

**Accessibility:** All timing labels include aria-label, high contrast

### Phase 5 — Control Tower Management Views

**Goal:** Operators can plan and intervene in one screen

**New dashboard sections in Filament / Blade admin:**

- Demand heatmap / time-band forecast per corridor: Use Chart.js heatmap — x=time_band 05:00-21:00, y=origin_junction, color=predicted_passengers, filter corridor
- Unserved demand: List demand_forecasts where predicted_passengers > supply*3, with gap count, sorted by gap desc — "Where are gaps tomorrow morning?" answered
- On-time performance at junctions: Table trips with variance >5 min late, group by junction, show % on-time
- Vehicle / trip utilisation: seats filled / total seats per trip, per corridor, per driver
- Ability to nudge drivers: Button "Nudge 5 drivers" → calls DriverPromptService
- Allocate programme buses: Form to add programme_bus_interests → appears as supply
- Export for FERMA / city partners: CSV of demand_forecasts + road_segments IRI

**Aligns with open-data story:** Publish anonymized demand_forecasts daily to open data portal

### Phase 6 — Hardening & Algorithms

- Keep OSRM for all path geometry — no custom A* implementation — document in code: "A* is internal to OSRM, we use OSRM HTTP API"
- Matching remains extended weighted scorer: `TripMatchingService::score()` — corridor exact match 40pts, time window within 30min 30pts, seats available 10pts, verification_level 10pts, leaving-soon bonus 5pts, IRI penalty if poor road -5pts, soft prefs (women-only) +5pts — total 100. Keep atomic lock `SELECT ... FOR UPDATE` on trips.available_seats
- Demand forecasting starts rule-based — only add heavier ML if data volume >1000 bookings/day and accuracy <70% justifies
- All new surfaces respect feature flags, accessibility, design system (Forest Green, glassmorphism cards, 8px grid)
- Tests: Add `JourneyServiceTest`, `DemandForecastJobTest`, `DriverPromptServiceTest` — must pass with FEATURE_SERVICE_PLANNING=true/false

---

## WHAT WE EXPLICITLY WILL NOT DO (YET):

- Full BRT-style vehicle scheduling / blocking / crew rostering — WorkRide doesn't own large fleet
- Replacing OSRM with custom A* engine — OSRM already uses A* internally
- Dynamic pricing — fixed fares invariant
- Assuming WorkRide owns large dedicated bus fleet — mixed-fleet only
- Publishing rigid public timetables conflicting with 30-min matcher — we publish arrival windows, not rigid timetables

---

## SCHEMA SUMMARY — MIGRATIONS TO CREATE:

1. corridor_junctions
2. trip_junction_times
3. demand_forecasts (or extend forecasts)
4. programme_bus_interests
5. Add journey fields to trips
6. Ensure junctions has passenger_volume_daily, is_major_hub (from seeding prompt with 45 Abuja locations: Nyanya Under-Bridge 5000, Berger, Kubwa Junction 2000, Lugbe, Zuba, Suleja, Gwagwalada, Mararaba, Masaka, etc)

---

## IMPLEMENTATION SEQUENCE FOR AI CODE GENERATOR (ORDERED):

1. **Junction model + corridor_junctions seeder** — Order 45 junctions per corridor by real road sequence — Use data from WORKRIDE-PROMPT-SEEDING-DATA.md (Zuba → Madalla → Kubwa → Berger → CBD etc)
2. **RoutingService OSRM integration** — HTTP client, cache, handle OSRM down fallback to straight-line estimate
3. **JourneyService plan() + liveProgress() + recordJunctionArrival()** — Core
4. **Expose journey timing on Connect Guide, My Rides, Driver view** — API + Blade
5. **Reverb events: TripLocationUpdated → check geofence → record arrival → broadcast JunctionArrived**
6. **Simple demand forecast job + storage + scheduler hourly**
7. **Demand-aware empty states and driver prompts (FCM)**
8. **Control Tower demand & on-time views (Filament widgets + Chart.js heatmap)**
9. **Soft programme-bus interest → matching pool**
10. **Polish timing labels everywhere + accessibility + feature flags + tests + DEVELOPMENT-LOG.md update**

---

## SUCCESS METRICS (Must be measurable in Control Tower):

- % trips with complete junction-level plans (target 100% for trips with corridor defined)
- Reduction in unserved demand (predicted passengers left without seats) — target <10% gap
- Passenger-visible ETA accuracy at junctions — target <3 min variance
- Driver response rate to demand prompts — target >20%
- On-time arrival at key junctions (Berger, AYA, Wuse Market) — target >85% within 5 min
- Control Tower can answer "Where are gaps tomorrow morning?" in one screen

---

## DELIVERABLES:

- Migrations, Models (CorridorJunction, TripJunctionTime, DemandForecast, ProgrammeBusInterest), Factories, Seeders
- Services: RoutingService, JourneyService, DriverPromptService, DemandForecastService
- Jobs: GenerateDemandForecastJob
- Events: JunctionArrived, TripLocationUpdated handling
- Controllers: Update TripController, BookingController, new JourneyController
- Frontend: Journey stepper component `resources/views/components/journey-stepper.blade.php` (Alpine, Tailwind glassmorphism, timeline), Connect Guide integration, Driver live view
- Control Tower: Filament widgets — DemandHeatmapWidget, UnservedDemandWidget, OnTimePerformanceWidget, ProgrammeBusInterest form
- Tests: JourneyServiceTest, DemandForecastJobTest, TripJunctionTimeTest
- Feature flags in config/workride.php
- DEVELOPMENT-LOG.md Sprint 5.0 update
- README section explaining why OSRM not custom A*, why weighted scorer not A* for matching

Build this — This makes WorkRide a true Corridor Service Planning platform, not just ride-share, and gives you data to negotiate BRT partnership with FCTA.

