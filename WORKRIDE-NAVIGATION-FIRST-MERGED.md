# WorkRide v5.0 — Navigation-First Redesign: Merged Implementation Plan

> Companion to the source prompts in `gallery_of_files/` (`input section.txt`,
> `WORKRIDE-MASTER-ORCHESTRATION-v5-NAVIGATION-FIRST.md`, `SPRINT-1..4`). This document
> is the **reconciled** plan: the learnings from `input section.txt` merged with the
> instructions already present in the 4 sprint prompts + master orchestration, aligned
> to the real codebase state (v0.21.0, Laravel 13.23, Tailwind v4, 428+ tests).
> Last updated: 2026-08-06

---

## 1. What this merge does

`input section.txt` contains two drafts (a §§1–8 version and a redrafted §A–H version)
of the navigation-first guide. The 4 sprint prompts + master orchestration are already a
distillation of both. **No content is missing from the sprint docs vs. the raw data** — the
merge is a conflict-resolution + codebase-alignment pass, not a content fill.

## 2. Source reconciliation

| `input section.txt` teaching | In the 5 instruction docs? | Status |
|---|---|---|
| Branding tokens, mobile rider / desktop admin | Sprint 1 §1.1–1.2, master §F | Present |
| Navigation-first Home + search + corridor chips | Sprint 2 §2.2–2.3 | Present |
| Map: CartoDB tiles, fitBounds, arrows, no empty zoom | Sprint 2 §2.3, master §B | Present |
| Share link `/trips/{id}/share?ref=` | Sprint 2 §2.4 | Present |
| Waypoint progress tracker + `WaypointReached` | Sprint 3 §3.1 | Present |
| Timing indicators + 30s refresh | Sprint 3 §3.2 | Present |
| Progress wizards (booking/publish/verify) | Sprint 3 §3.3 | Present |
| Share request/approval flow + `referred_by` | Sprint 3 §3.4 | Present |
| Admin 5-package grouping + role switcher | Sprint 1 §1.3–1.4 | Present |
| Bus scheduling + recurring trips job | Sprint 4 §4.1 | Present |
| MapLibre flagged upgrade (pitch 35–55°, fallback) | Sprint 4 §4.2 | Present |
| A11y / 3G / PWA polish | Sprint 4 §4.3 | Present |
| FCM push on 500 m geofence | Sprint 3 §3.2 | Partial — see §3.8 |
| `RoutingService::geocode(q)` fallback | Sprint 2 §2.1 | Partial — method missing |
| "Leaves in X / ETA Y / Next: N" wording everywhere | Sprint 3 §3.2 | Present |

## 3. Conflicts & merge decisions

1. **Brand palette conflict (highest impact).** Raw data proposes `--color-primary: #0F5132`
   (Deep Forest) + `--color-accent: #FFC300`. Shipped `resources/css/design-system.css`
   (v0.10.0) is the guide-mandated source of truth (`WORKRIDE-APP-GUIDE §17`: Forest Green
   `#2E7D32`, Gold `#FBC02D`, Slate `#0F172A`, Paper `#F6F9F6`) in Tailwind v4 `@theme`
   (`--color-forest-*`, `--color-gold-*`, `--color-ink-*`, `--color-paper`).
   **Decision:** keep `#2E7D32` canonical; add aliases so sprint-doc snippets run verbatim:
   `--color-primary: var(--color-forest-600)`, `--color-primary-light: var(--color-forest-500)`,
   `--color-accent: var(--color-gold-400)`, `--color-surface: var(--color-paper)`,
   `--color-ink: var(--color-ink-900)`, plus `--radius-card/--radius-pill/--shadow-card/--shadow-live`.
2. **`tailwind.config.js` instruction is moot.** Tailwind v4 has no JS config; tokens live in
   `@theme`. Skip that doc step.
3. **Rider bottom nav already exists** (`components/mobile-nav.blade.php`, Sprint 9: Trips, My
   Rides, Wallet, Rewards, More). **Decision:** reorder to navigation-first IA in Sprint 2 (Home
   becomes the hero, not a tab). The `max-w-[480px]` mobile container is genuinely new — apply to
   rider layouts, keep desktop `max-w-5xl`.
4. **Admin sidebar is flat, ~19 items, desktop-only, and the app is NOT Filament.** Group into
   5 packages via `config/admin_nav.php` + custom Blade Alpine accordion (docs allow "if custom
   Blade"), reuse `x-admin-nav-link`, add badge counts + admin mobile drawer/bottom nav.
5. **Role switcher is genuinely new.** `view_as_role` in session is **display-only** — never
   bypasses `admin` middleware or verification gates. Add a "viewing as" banner.
6. **Share page exists but is static** (v0.10.0). Extend: `trips.share_code` + `bookings.referred_by_user_id`,
   QR + Web Share, "Request to join" → `requested` booking feeding the Sprint 3 approval flow.
7. **FCT bounds exist in config** (`config/workride.php` → `fct_bounds` 8.6/9.4/6.9/7.7) — reuse for
   `setMaxBounds` + `minZoom:10`; ignore doc hardcode.
8. **FCM doesn't exist; neither does `NotificationService`.** Notifications use `database`+`log`
   (`SendPhoneOtp` pattern); FCM is roadmap P2. **Decision:** 500 m "driver arriving" geofence notify
   as database/log now; FCM stays a flagged follow-up.
9. **`RoutingService::geocode()` doesn't exist** (only `route(from,to,profile)`). Add it (OSRM
   `/geocode/search` fallback) in Sprint 2.
10. **Naming/watch-outs.** `Schedule` model exists (Sprint 11 ops roster) — `BusSchedule`/`bus_schedules`
    is distinct. `TripService` already has `registerInterest`/`updateLocation`/`completeTrip` —
    progress/timing methods slot in alongside. Master doc's "Laravel 11 / 384 tests" is stale
    (actually 13.23 / 428+); no code impact.

## 4. Merged per-sprint plan

Icons: ✅ shipped → extend · 🔧 merge doc intent with existing code · ➕ genuinely new.

### Sprint 1 — Foundation, Branding, Admin ✅
- 🔧 ✅ Token aliases in `design-system.css` (`--color-primary`, `--color-accent`, `--color-surface`, `--radius-card`, `--shadow-card` …); skip `tailwind.config.js` (Tailwind v4); keep `#2E7D32` in manifest.
- ✅ Rider layout container → `max-w-[480px] ... lg:max-w-5xl`.
- ✅ `config/admin_nav.php` (5 groups) + `admin-sidebar` Alpine accordion component + badge counts (verifications / employer pending) + admin mobile drawer + bottom tab bar.
- ✅ `RoleSwitcherService` (display-only, session `view_as_role`) + `EffectiveRoleMiddleware` (web group) + "Viewing as … — Back to admin" banner + topbar dropdown.
- ✅ `npm i leaflet-polylinedecorator leaflet-arrowheads maplibre-gl` + `resources/js/map/common.js` (CartoDB tiles, FCT maxBounds, fitOrCenter, `addRouteLine` w/ arrowheads, `corridorAnchor`).
- ✅ `ui/card` + `ui/button` Blade components wired to the design tokens.
- ✅ Icons: `menu`, `users`, `map`, `settings`, `truck`.
- ✅ `NavigationFirstTest` (7) — gate: `pint` + PHPStan L8 (baseline regenerated) + 466 tests green + build.

### Sprint 2 — Navigation Home + Search + Map + Share ✅
- ✅ `NavigationController` (web `/go` + API `search|directions|nearby`) + `NavigationService` (TripMatching+Geofence+Routing+Demand);
  search = junctions (45) + workplaces + ✅ `RoutingService::geocode` (Nominatim, free, never-throw).
- ✅ Rider Home rewrite (`Where are you going?` hero, corridor chips w/ live counts, map canvas, bottom sheet) at `/go`.
- ✅ Map fixes: shared `map/common.js` (CartoDB tiles, fitBounds, minZoom/maxBounds from config), never-empty board
  (CBD fallback), demand-aware empty state (`DemandService::demandSnapshot()`), `?ref=`-aware Web Share.
- ✅ Share: `share_code` + QR + Web Share + `?ref=` session referral → `bookings.referred_by_user_id` + audit log.
- ✅ Tests `NavigationTest` (8) — gate: `pint` + PHPStan L8 (baseline regenerated) + 474 tests green + build.

### Sprint 3 — Live Progress + Timing + Wizards + Share Request
- ➕ Waypoint migration (`eta_minutes`, `is_major_hub`, `distance_from_origin_km`, `geofence_radius_m`, `reached_at`).
- ➕ `TripService::calculateProgress()` + `getTimingAttributes()`; 🔧 extend `TripLocationUpdated` payload;
  ➕ `WaypointReached` event → `reached_at`, activity_log, DB/log notify.
- ➕ `components/trip/progress-tracker.blade.php` (passenger/driver/admin).
- ➕ `components/ui/progress-wizard.blade.php`; apply to booking (wrap `payment-picker`), publish, verify.
- ➕ Timing surfaces + 30 s refresh; 🔧 reuse `ConnectGuideService` walking numbers.
- 🔧 Share request: request → `requested` booking + referral → driver approve via atomic book path; waiting list.

### Sprint 4 — Scheduling + MapLibre + Polish
- ➕ `bus_schedules` + `BusSchedule`, `SchedulingService`, `GenerateRecurringTripsJob` (daily 5am, `SCHED-…` idempotent).
- 🔧 Carpool recurring + "Leave now" toggle on `trips/create`.
- ➕ Admin scheduling page (FullCalendar) + demand hints from `ForecastService::suggest()/learned()`.
- ➕ MapLibre behind `FEATURE_MAPLIBRE` (default false), dynamic import, Leaflet fallback, pitch 35–55° guide/live only.
- 🔧 A11y/perf polish on existing motion-token + a11y base.

## 5. Execution protocol

1. Sprint in order [1,2,3,4]. After each piece: `vendor/bin/pint && npm run build && php artisan test`.
2. After each sprint: full DoD ritual (`WORKRIDE-DEV-GUIDE §5`) + one commit
   (`feat(nav): sprint N - …`) + tag `v5.0.0-navigation-first` at the end.
3. Never start Sprint N+1 until Sprint N acceptance passes.
4. Update `DEVELOPMENT-LOG.md` + this file after each sprint.

## 6. Definition of Done (from `input section.txt` §G)

- Opening app immediately presents "Where are you going?"
- Destination → join live / book / share in very few taps
- Map never empty/meaningless; route + labels + pins + direction arrows always visible
- Live trip progress through junctions visible to passenger, driver, admin (same component)
- Share link allows colleague to request to join ongoing ride safely (approval + atomic seat + referral)
- Admin sidebar grouped into 5 collapsible packages, usable on mobile
- Progress steppers on booking, publish, verification flows with timing hints
- Timing indicators everywhere (leaves/ETA/next/to-pickup + 500 m geofence notify)
- Bus scheduling: admin creates schedule, job generates Trips daily 5am, passenger sees next 3 departures
- MapLibre flagged upgrade (tilted 35–55° + vector labels + Forest Green route) with Leaflet fallback
- Rider + Admin share the same design-system.css tokens
- All money, verification, seat invariants intact (decimal 15,2 · NIN hash · FOR UPDATE + version · idempotent refs · activity_logs)
- A11y: VoiceOver announces junction changes, reduced-motion respected, touch ≥ 44px
- Perf: <2s on 3G, bundle split, Lighthouse >80
- Tests green, pint clean, phpstan no new errors, build succeeds, `migrate:fresh --seed` < 30s, `gtfs:generate` valid
- DEVELOPMENT-LOG.md updated
