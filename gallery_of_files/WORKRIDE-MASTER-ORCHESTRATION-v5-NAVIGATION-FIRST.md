# WORKRIDE v5.0 — MASTER ORCHESTRATION PROMPT
## Navigation-First Redesign + Map Upgrade + Control Tower Mobile Fix
### Single Prompt to Rule All 4 Sprints — For Cursor / Claude Code / Windsurf / Any AI Coder

> **Source of Truth:** `WORKRIDE-APP-GUIDE.md` v1.0 + `WORKRIDE-DEV-GUIDE.md` + `WORKRIDE-USER-GUIDE.md` + `input_section.txt` (your navigation-first mandate)
> **Goal:** Transform feature-complete dashboard app into award-winning navigation-first app that beats Google Maps for Abuja's informal junctions
> **Stack:** Laravel 11 + Blade + Alpine.js + Tailwind + MySQL 8 + Redis + Reverb + Leaflet → MapLibre (flagged) + OSRM RoutingService
> **Non-Negotiables (DEV-GUIDE §1):** Never store raw NIN (hash only), money decimal(15,2), atomic with FOR UPDATE + version optimistic locking, idempotent references, activity_logs for every trust action, tests on SQLite + MySQL, pint + phpstan level 8 + build must pass

---

## ROLE
You are Staff Engineer + Product Designer who shipped Citymapper + Transit + Uber Shuttle + Watch Duty (2025 Apple Design Award Social Impact) + Oko (2024 Inclusivity). You know Abuja reality: 50% salary on transport, 5,000 wait at Nyanya Under-Bridge 6:30am, MTN 3G, low-end Android. You execute step-by-step, small commits, never break money/verification/seat invariants.

## MASTER PLAN — 4 SPRINTS IN ORDER (Do NOT skip order)

### You have 4 detailed sprint prompts in /mnt/data:
1. `SPRINT-1-FOUNDATION-BRANDING-ADMIN.md` — Branding + Admin Grouping + Role Switcher + Map Libs
2. `SPRINT-2-NAVIGATION-HOME-SEARCH-MAP.md` — Navigation-First Home + Search + Map Fixes + Share Link
3. `SPRINT-3-LIVE-PROGRESS-TIMING-WIZARDS.md` — Live Junction Progress + Timing + Wizards + Share Request
4. `SPRINT-4-SCHEDULING-MAPLIBRE-POLISH.md` — Bus Scheduling + MapLibre Tilted + Final Polish

**Execution Protocol:**

```
For Sprint in [1,2,3,4]:
  1. Read SPRINT-{N}*.md fully
  2. Execute all tasks in that file in order
  3. After each major piece: vendor/bin/pint && npm run build && php artisan test (subset)
  4. Commit: git add <relevant> && git commit -m "feat(nav): sprint N - <what>"
  5. Do NOT start Sprint N+1 until Sprint N acceptance criteria pass
  6. Update DEVELOPMENT-LOG.md with Sprint N summary
```

**Global Commands to Run After Each Sprint:**
```bash
vendor/bin/pint
vendor/bin/phpstan analyse
php artisan test
npm run build
php artisan migrate:fresh --seed # must complete <30s, no FK errors, 45 junctions seeded
php artisan gtfs:generate # must generate valid zip
```

---

## CROSS-SPRINT ARCHITECTURE DECISIONS (From input_section.txt)

### A. Navigation-First Mental Model (Priority from input_section.txt §1)

**New Home IA:**
1. Home/Search: Large "Where are you going?" + recent/favourite + live Corridor Chips as shortcuts
2. Results: List + map of options: Join live, Book ahead, Demand check-in, Share link
3. Booking/Join: Payment + seat confirmation (existing atomic flow)
4. Connect Guide + Live Progress: Walking guide + live progress through junctions
5. My Rides/Wallet/Impact/More: Secondary

**Scenarios to support:**
- Join already-moving car/bus going my way (live seat + share link)
- Book future seat on published corridor trip
- Driver publishes → passengers join
- Passenger on trip shares private link so colleague can request to join same vehicle
- Demand signal "I'm at Berger, need CBD" seeding empty states
- Live progress visible to passenger, driver, Control Tower as vehicle passes junctions

### B. Map Quality — Two-Phase Path (From input_section.txt §2)

**Phase 1 Immediate (Sprint 2): Keep Leaflet, fix empty-zoom**
- Better tiles: CartoDB Positron/Voyager, Stadia, or OpenFreeMap raster
- Always fitBounds with padding 40-80px when showing origin+destination+route
- Direction arrows: Leaflet.PolylineDecorator
- minZoom + maxBounds FCT, labels-only overlay if needed

**Phase 2 High-Value (Sprint 4): MapLibre GL JS**
- Vector tiles: OpenFreeMap (no key, unlimited) or Protomaps
- Moderate pitch 35-55° for gentle front-of-road view during active navigation — NOT extreme 70-85° on low-end
- Vector tiles = sharp road labels, brandable Forest Green routes
- Keep Leaflet fallback, feature-flag FEATURE_MAPLIBRE

**Library Comparison (from research):**
Leaflet ~40kb No tilt Limited Free — Excellent for 3G
MapLibre ~200-290kb Yes (85°) Excellent Yes Free — Best upgrade
Mapbox ~300kb Yes Excellent Yes Paid — Avoid for cost

### C. Live Progress Through Junctions (input_section.txt §3)
- Define named junctions/milestones per corridor (Berger, Nyanya, Lugbe etc) — already in demand/GTFS thinking
- As vehicle crosses geofence, broadcast Reverb event
- Shared Progress Tracker: Booked → Approaching Berger → At Berger → En-route → Arriving
- Visual: horizontal/vertical stepper, current highlighted Forest Green
- Visible to passenger, driver, Control Tower — major differentiator

### D. Admin Grouping + Mobile (input_section.txt §4)
Group into packages (Filament NavigationGroup or Blade):
- Operations: Live Trips, Demand, Fleet, Verifications
- People: Users, Drivers, Employers
- Intelligence: Road Map, GTFS, Impact, Reports
- Business: Wallets, Subsidies, Settlements, API keys
- System: Settings, Feature Flags, Logs

Groups collapsible, sidebar becomes hamburger/bottom sheet on mobile, tables Stack, touch >=44px, same design tokens.

### E. Progress Wizards (input_section.txt §5)
- Booking: Destination → Options → Payment → Confirmed
- Driver Publish: Corridor → Time/Seats → Vehicle/Inspection → Publish
- Verification upgrades
- Connect Guide mini progress
- Use simple horizontal stepper matching design system

### F. Branding (input_section.txt §6)
Same design-system.css tokens everywhere (Rider + Admin).

---

## DETAILED FILE MANIFEST (All files you will create/modify across 4 sprints)

**Config & CSS:**
- `resources/css/design-system.css` (CREATE)
- `resources/css/app.css` (UPDATE import)
- `tailwind.config.js` (UPDATE colors)
- `config/admin_nav.php` (CREATE)
- `config/workride.php` (UPDATE add FEATURE_MAPLIBRE, FEATURE_NAVIGATION)
- `public/manifest.json` (UPDATE)

**Layouts & Components:**
- `resources/views/layouts/app.blade.php` (UPDATE mobile-first max-w-[480px] + bottom-nav)
- `resources/views/layouts/admin.blade.php` (UPDATE grouped sidebar + drawer)
- `resources/views/layouts/public.blade.php` (ENSURE guest-safe)
- `resources/views/components/ui/card.blade.php` (CREATE)
- `resources/views/components/ui/button.blade.php` (CREATE)
- `resources/views/components/ui/badge.blade.php` (CREATE)
- `resources/views/components/ui/progress-wizard.blade.php` (CREATE Sprint 3)
- `resources/views/components/navigation/bottom-nav.blade.php` (CREATE Sprint 1)
- `resources/views/components/map/navigation-map.blade.php` (CREATE Sprint 2, UPDATE Sprint 4 for MapLibre)
- `resources/views/components/trip/progress-tracker.blade.php` (CREATE Sprint 3)
- `resources/views/components/brand/logo.blade.php` (UPDATE)

**Rider Pages:**
- `resources/views/home.blade.php` or `trips/index.blade.php` (REWRITE Sprint 2 navigation-first)
- `resources/views/trips/show.blade.php` (UPDATE with progress tracker + timing)
- `resources/views/trips/share.blade.php` (CREATE Sprint 2 public share)
- `resources/views/bookings/create.blade.php` (UPDATE with wizard Sprint 3)
- `resources/views/trips/publish.blade.php` (UPDATE with wizard + recurring toggle Sprint 3/4)

**Admin Pages:**
- `resources/views/admin/trips/live.blade.php` (UPDATE with progress tracker)
- `resources/views/admin/scheduling/index.blade.php` (CREATE Sprint 4 with FullCalendar)

**Backend:**
- `app/Services/RoleSwitcherService.php` (CREATE Sprint 1)
- `app/Http/Middleware/EffectiveRoleMiddleware.php` (CREATE Sprint 1)
- `app/Http/Controllers/Api/V1/NavigationController.php` (CREATE Sprint 2)
- `app/Services/NavigationService.php` (CREATE Sprint 2)
- `app/Services/SchedulingService.php` (CREATE Sprint 4)
- `app/Models/BusSchedule.php` + migration (CREATE Sprint 4)
- `app/Jobs/GenerateRecurringTripsJob.php` (CREATE Sprint 4)
- `app/Services/TripService.php` (UPDATE calculateProgress, getTimingAttributes)
- `app/Events/WaypointReached.php` (CREATE Sprint 3)
- `app/Models/Trip.php` (UPDATE share_code)
- `app/Models/TripWaypoint.php` (UPDATE with eta, is_major_hub, geofence, reached_at)
- `app/Models/Booking.php` (UPDATE referred_by, share_code)
- `database/migrations/*_enhance_trip_waypoints.php` (CREATE Sprint 3)
- `database/migrations/*_add_share_code_to_trips.php` (CREATE Sprint 2)
- `routes/api.php` (UPDATE navigation routes)
- `routes/web.php` (UPDATE share route)

**JS:**
- `resources/js/app.js` (UPDATE imports)
- `resources/js/map/common.js` (CREATE Sprint 1)
- `resources/js/map/navigation.js` (CREATE Sprint 2)
- `resources/js/map/maplibre.js` (CREATE Sprint 4)
- `resources/js/navigation/search.js` (CREATE Sprint 2 Alpine component)

**NPM:**
- `leaflet-polylinedecorator`, `leaflet-arrowheads`, `maplibre-gl`, `@fullcalendar/*`

---

## DEFINITION OF DONE FOR WHOLE EXTENSION (From input_section.txt §G)

- [ ] Opening app immediately presents "Where are you going?"
- [ ] User can go destination → join live / book / share in very few taps
- [ ] Map never shows empty/meaningless view; route + labels + pins always clear, direction arrows visible
- [ ] Live trip progress through junctions visible to passenger, driver, admin (same component)
- [ ] Share link allows colleague to request to join ongoing ride safely with approval + atomic seat check + referral logged
- [ ] Admin sidebar grouped into 5 collapsible packages, usable on mobile (hamburger + bottom nav), tables responsive
- [ ] Progress steppers on booking, publish, verification flows with timing hints
- [ ] Timing indicators everywhere: "Leaves in X", "ETA Y (Z mins left)", "Next: ... in N mins", "Time to pickup: N walk", geofence push
- [ ] Bus scheduling: Admin can create schedule every 15 mins peak, job generates Trips daily 5am, passenger sees next 3 departures
- [ ] MapLibre flagged upgrade: When enabled, Connect Guide shows tilted 35-55° pitch with vector road labels, Forest Green route — looks better than Google Maps for this job, fallback to Leaflet on low-end
- [ ] Entire app Rider + Admin uses same design-system.css tokens, feels one coherent premium product
- [ ] All money, verification, seat invariants intact: decimal(15,2), NIN hash only, FOR UPDATE + version, idempotent references, activity_logs
- [ ] Accessibility: VoiceOver announces junction changes, reduced-motion respected, keyboard nav, touch >=44px
- [ ] Performance: <2s on 3G throttling, bundle split, Lighthouse >80, <50kb JS for Leaflet path
- [ ] Tests green: `php artisan test` 384+ tests, `pint` clean, `phpstan` no new errors, `npm run build` succeeds, `migrate:fresh --seed` <30s with 45 junctions, `gtfs:generate` valid
- [ ] DEVELOPMENT-LOG.md updated

---

## FINAL INSTRUCTION (From input_section.txt §H)

Protect the engineering excellence already shipped.
Elevate the product so first three seconds feel like world-class navigation tool, while deeper experience remains uniquely WorkRide (verified colleagues, fixed fares, junction intelligence, Time-Bank, social impact).

Make the search box the new front door.
Make the map clear, labelled, and slightly perspective when guiding.
Make progress through the city visible and trustworthy.
Make Admin usable on a phone.

Execute step-by-step. Prefer clarity and real-world performance on Abuja 3G devices.

---

## HOW TO USE THIS MASTER PROMPT

If you are Cursor / Claude Code / Windsurf:

1. Paste this entire file as your main prompt
2. Then attach SPRINT-1 to SPRINT-4 files as context
3. Say: "Execute Sprint 1 now. After passing acceptance, ask for confirmation before Sprint 2."

If you are human dev:

1. Do Sprint 1 → commit → test
2. Do Sprint 2 → commit → test
3. Do Sprint 3 → commit → test
4. Do Sprint 4 → commit → test
5. Tag v5.0-navigation-first

Good luck — this will make WorkRide feel like Citymapper + Watch Duty + Transit combined, built for Abuja's real junctions (Nyanya Under-Bridge, Zuba, Suleja, Gwagwalada, Lugbe) — exactly what wins funding + FCTA approval.

End of master orchestration prompt.
