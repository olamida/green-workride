# WorkRide — Development Log & Setup Documentation

> Companion to `WORKRIDE-APP-GUIDE.md` (the product spec). This document tracks the
> actual development work completed so far on the Green WorkRide platform.
> Last updated: 2026-08-01

---

## 1. Project Overview

**Green WorkRide** — Community-Focused, Subsidy-Enabled Transit Intelligence Platform.

- **Vision:** 3 layers — Ride-share & staff bus aggregator, GTFS publisher (first for Abuja), road intelligence network.
- **Architecture:** Dual-app system — 1) Rider PWA, 2) Ops Control Tower.
- **Business:** Community Interest Company (CIC) hybrid — 60% Community Trust, 40% For-Profit Operating Co.
- **Tagline:** *"Built by amateurs, for the working class. From Abuja to the world."*

The authoritative product specification is `WORKRIDE-APP-GUIDE.md` in this folder.

---

## 2. Current Status (Phase: Foundation / Sprint 9 + Sprint 3.6 + Investor-Guide Adoptions A–F Complete)

| Area | Status |
|------|--------|
| Project scaffolding | ✅ Done — Laravel 13.23.0 installed |
| Database | ✅ Done — MySQL `workride` created, base migrations run |
| Frontend build | ✅ Done — Vite/Tailwind build passes |
| AI development tooling | ✅ Done — opencode + Laravel Boost MCP wired up |
| Core stack packages | ✅ Done — Sanctum, Reverb, Telescope installed |
| Feature modules | ✅ Sprint 1 complete — Auth + Verification + Ops Control Tower |
| Feature modules | ✅ Sprint 2 complete — Trip publishing + atomic booking + Reverb chat |
| Feature modules | ✅ Sprint 3 complete — Wallet dual balance + Paystack top-up + Subsidy bulk credit |
| Feature modules | ✅ Sprint 4 complete — GTFS Publisher (static feed + GTFS-RT) |
| Feature modules | ✅ Sprint 5 complete — Road Sensor + Road Intelligence (IRI, pothole clustering, FERMA export) + Routing API cost caps |
| Feature modules | ✅ Sprint 6 complete — PWA award UI + Impact certificates (CO₂/Fuel, QR-verifiable) + Impact analytics + demo users |
| Feature modules | ✅ Sprint 3.5 complete — Time-Bank (ride credits) + Earned wallet + P2P transfers + Payouts (feature-gated `FEATURE_TIME_BANK`) |
| Feature modules | ✅ Sprint 7 complete — Business dashboard (KPIs + revenue/corridor/subsidy charts) + 5 QR-verifiable financial receipts + CSV exports (transactions, settlements, subsidy) |
| Feature modules | ✅ Sprint 8 complete — Employer Mobility Programs (org-funded commutes) + Reward Campaigns & Green Points economy + Commodity Commerce (wallet → gold/rice/maize/fuel positions + QR shop orders), all feature-gated |
| Feature modules | ✅ Sprint 9 complete — Missions (sponsor-defined promoted activities with auto-verified + photo-proof rewards) + global nav redesign (⌘K command palette, profile menu, mobile tab bar, searching-algorithm SVG brand mark) |
| Feature modules | ✅ Sprint 3.6 complete — Tiered KYC (open staff-ID liveness $0 + NIMC-licensed NIN lookup + Smile Identity driver anti-spoof), verification attempts + rate limit, encrypted selfie retention, Control Tower cost dashboard (feature-gated `FEATURE_LIVENESS`, `USE_IDENTITYPASS`, `USE_SMILE`) |
| Feature modules | ✅ Investor-guide adoptions A–F complete — Mutual ride ratings + driver scoreboard (Control Tower) · Safety pack (public Share Trip page, one-tap SOS into change-control trail, emergency contact) · Women-only preference (never a hard sort) · Offline trip board (PWA SW read-only cache + `/offline`) · Design tokens file (`design-system.css`) · Landing investor KPI strip |
| Tests | ✅ 309 feature tests passing (auth, verification, admin, trips, bookings, chat, wallet, subsidy, GTFS, road sensor, road intelligence, routing, impact, PWA, ride credit, earned wallet, P2P transfer, business dashboard, receipts, employer programs, rewards/green points, commodity commerce, missions, tiered verification, selfie retention, ratings, safety, women-only) |

---

## 3. Environment

| Component | Version / Value |
|-----------|-----------------|
| PHP | 8.3.30 (ZTS, Visual C++ 2019) |
| Laravel | 13.23.0 (latest at install time) |
| Composer | 2.8.3 |
| Node.js | v24.15.0 |
| npm | 11.12.1 |
| Database | MySQL 8 (via Laragon) — database name `workride`, user `root`, no password |
| Cache / Queue / Session | database driver (Redis not running locally yet) |
| Broadcasting | Reverb (installed, config active) |
| URL | `http://localhost/dev-angle/Starter-folder/workride/public` |

---

## 4. Work Completed

### 4.1 Project Creation
- Created project at `D:\Softwares\laragon\www\dev-angle\Starter-folder\workride`
- `composer create-project laravel/laravel workride` (installed `laravel/laravel v13.8.0` → framework `v13.23.0`)
- `APP_KEY` generated, `.env` created
- Node dependencies installed (`npm install`), frontend built (`npm run build`)
- Git repository initialized (no commits made yet)

### 4.2 Database Setup
- Created MySQL database `workride` (utf8mb4 / utf8mb4_unicode_ci)
- Switched `.env` from default SQLite to MySQL
- Ran base migrations: users, cache, jobs tables
- Ran package migrations: telescope entries, personal access tokens
- `php artisan storage:link` connected public storage

### 4.3 Environment Configuration (`.env`)
- `APP_NAME=WorkRide`
- `APP_URL=http://localhost/dev-angle/Starter-folder/workride/public`
- `DB_CONNECTION=mysql`, `DB_DATABASE=workride`
- `MAIL_FROM_ADDRESS=hello@workride.ng`
- `.env.example` updated to mirror the same MySQL/WorkRide settings for fresh clones

### 4.4 Installed Packages

**Production:**
| Package | Version | Purpose (per guide) |
|---------|---------|---------------------|
| `laravel/sanctum` | ^4.3 | API V1 auth (`routes/api.php`, tokens) |
| `laravel/reverb` | ^1.11 | WebSockets — trip chat, live driver location |
| `laravel/tinker` | ^3.0 | REPL for quick queries |

**Dev / AI tooling:**
| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/boost` | ^2.4 | MCP server (`php artisan boost:mcp`) for opencode |
| `laravel/mcp` | 0.9.1 | MCP support (dependency of Boost) |
| `laravel/telescope` | ^5.21 | Debugging dashboard at `/telescope` |
| `laravel/pail` | ^1.2.7 | Real-time log tailing (`php artisan pail`) |
| `laravel/pint` | ^1.30 | Code style fixer |
| `barryvdh/laravel-ide-helper` | ^3.7 | Autocomplete — generated `_ide_helper.php` + `_ide_helper_models.php` |

### 4.5 AI Development Tooling
- **`opencode.json`** — instructions reference `WORKRIDE-APP-GUIDE.md`; Laravel Boost MCP enabled via local command `php artisan boost:mcp`; LSP enabled
- **`boost.json`** — `agents: [opencode]`, `mcp: true`, cloud/nightwatch/sail disabled
- This mirrors the proven setup from the `naptin-coop` project

### 4.6 Code Changes
- **`app/Models/User.php`** — added `Laravel\Sanctum\HasApiTokens` trait (required for API auth)
- **`phpunit.xml`** — added `APP_URL=http://localhost` override (fixes feature tests when the app is served from a nested path)
- **`.gitignore`** — added `_ide_helper_models.php`

### 4.7 Sprint 1 — Auth + Verification + Ops Control Tower (COMPLETE)

**Enums (`app/Enums`, 10 files):** `UserRole`, `VerificationLevel`, `TripStatus`, `BookingStatus`, `Corridor`, `PaymentMethod`, `TransactionType`, `RoadEventType`, `RoadCondition`, `VehicleType`. All backed enums with `label()`; `VerificationLevel` carries `canBook()/canDrivePaid()/canDriveVolunteer()`; `UserRole::assignableCases()` blocks self-assignment of `admin`/`workplace_admin` at registration.

**Schema (20 migrations total):** users upgraded with `phone, avatar, role, verification_level, workplace_id, nin_hash, nin_last4, is_banned`; core tables created — `workplaces`, `verifications`, `vehicles`, `trips`, `trip_waypoints`, `bookings`, `wallets`, `transactions`, `chat_messages`, `impact_stats`, `road_events`, `road_segments`, `activity_logs` (+ Sanctum/Telescope/cache/jobs).

**Models (14):** `Workplace`, `Verification`, `Vehicle`, `Trip`, `TripWaypoint`, `Booking`, `Wallet`, `Transaction`, `ChatMessage`, `ImpactStat`, `RoadEvent`, `RoadSegment`, `ActivityLog`, `User` (with relations + enum/boolean casts).

**Services (`app/Services`):**
- `VerificationService` — NIN hashed SHA-256 (raw never stored), document hashing via the `public` disk, admin approve/reject with level recompute (workplace=1, nin=2, driver=3).
- `GeofenceService` — FCT bounding box + 500 m radius workplace geofence checks.
- `PricingService` — fixed per-corridor anti-surge fares from `config/workride.php`.

**Middleware:** `EnsureAdmin`, `EnsureVerifiedWorker`, `EnsureDriverVerified`, `EnsureNotBanned` — aliased in `bootstrap/app.php` (`admin`, `verified.worker`, `driver.verified`, `not.banned`).

**Auth (web + API):** Blade register/login/logout with email + optional workplace auto-verification, banned-account login block, Google OAuth routes (disabled until `GOOGLE_CLIENT_ID` set). Sanctum `POST /api/v1/auth/register|login|logout`, `GET /api/v1/auth/me` returning plain-text tokens.

**Verification flow:** `/verify` page (Level 1 workplace ID upload + Level 2 NIN), API equivalents under `/api/v1/verifications/*`. Admin approve/reject with required rejection note.

**Ops Control Tower (web):** `/admin` dashboard (7 KPIs + recent verifications/users), verifications queue with status/type filters, users index with search/level filters + ban/unban (admins protected), workplaces index with zone filter. Slate sidebar + forest-green design system.

**Seeders:** `WorkplaceSeeder` (45 FCT MDAs with approx CBD coords + acronyms), `AdminUserSeeder` (`admin@workride.ng` / `admin1234`, config-driven), wired into `DatabaseSeeder`.

**Config:** added `workride.admin` block for seeded admin credentials.

**Tests (33 passing, 111 assertions):** `AuthTest`, `VerificationTest`, `AdminTest`, `ExampleTest`.

### 4.8 Sprint 2 — Trip Publishing + Atomic Booking + Reverb Chat (COMPLETE)

**Factories:** `VehicleFactory`, `TripFactory` (with `volunteer()`, `forDriver()`, `status()` states), `BookingFactory`, `ChatMessageFactory`.

**Services:**
- `TripService` — `publish()` (verifies driver level + FCT geofence, volunteer trips allow null vehicle, fixed fare from `PricingService`), `start()`, `updateLocation()`, `completeTrip()` (settles confirmed/boarded bookings), `cancelTrip()` (refunds each booking, resets seats).
- `BookingService` — atomic `book()` with `SELECT ... FOR UPDATE` trip lock; blocks self-booking / past departure / duplicates / oversell; payment methods wallet/cash/subsidy_credit; wallet holds, cash logged to driver `cash_collected_log`, free volunteer books with no hold. `cancelBooking()` refunds holds + frees seat, `board()` captures, `noShow()` captures 50% via `config('workride.no_show_capture_percent')`.
- `WalletService` — optimistic `version` locking, idempotent `BOOK-{id}-HOLD` references, hold → capture/refund by type mutation, subsidy-credits-first debit.
- `TripMatchingService` — `findMatches()` (corridor + 2 km Haversine radius + time window) and `upcoming()` (web board, no distance filter); distance via `GeofenceService::haversine()`.

**Events (broadcast):** `TripPublished`, `TripStarted`, `TripCompleted`, `TripCancelled`, `TripLocationUpdated`, `BookingConfirmed`, `BookingCancelled`, `NewChatMessage`. Public `trips` channel + private `trip.{id}` channel with `isParticipant()` auth in `routes/channels.php`.

**API (`/api/v1`):** `TripController` (index/store/show/location/start/complete/cancel), `BookingController` (index/store/cancel/board/no-show), `ChatController` (index/store). Sanctum protected.

**Web:** `TripBoardController` (`/trips` index/create/store/show + start/complete/cancel/messages), `BookingController` (`/bookings` index + cancel/board/no-show + `/trips/{trip}/book`). Views: `trips/board` (corridor chips), `trips/create` (Alpine geolocation), `trips/show` (passenger mgmt + driver actions + live chat/location), `bookings/index` (My Rides). Nav updated in `layouts/app.blade.php`.

**Realtime client:** `resources/js/bootstrap.js` (Echo + Reverb), `app.js` (Alpine), `trip-chat.js`, `trip-live.js`; npm packages `laravel-echo`, `pusher-js`.

**Config:** added `search_radius_m` (2000), `departure_window_minutes` (30), `no_show_capture_percent` (50) to `config/workride.php`.

**Tests (35 new — 68 total, 217 assertions):** `TripTest` (board access, publish gating L1 volunteer/L3 paid, fixed fare, FCT rejection, foreign vehicle rejection, API search, start/complete/cancel, driver-only rules), `BookingTest` (holds, subsidy-first, refunds, board capture, no-show half-capture, trip completion settlement, cash collection logging, API booking, guest redirect), `ChatTest` (participant-only auth, web+API send/view, validation).

### 4.9 Sprint 3 — Wallet Dual Balance + Paystack Top-up + Subsidy Bulk Credit (COMPLETE)

**Schema (1 new migration):** `add_gateway_columns_to_transactions_table` — nullable `tx_ref` (indexed) + `gateway_ref` on `transactions` for idempotent gateway reconciliation. `Transaction` model gains both fillables; `PaymentMethod` gains `Paystack`; `TransactionType` gains `TopUp`.

**Services:**
- `PaystackService` — thin HTTP client: `isConfigured()` (secret + webhook secret present), `initialize(email, naira, reference)` → `authorization_url` (kobo conversion at the edges), `verify(reference)`, `verifyWebhookSignature(payload, signature)` via HMAC-SHA512. Falls back to a synthetic 503 response when Paystack is unreachable.
- `WalletFundingService` — `referenceFor(user)` builds `WR-{userId}-{random}` (embeds the user so the webhook can resolve them without a pending-row lookup); `creditTopUp()` credits cash idempotently keyed by the Paystack `tx_ref` and stamps `type=top_up` + gateway refs; `handlePaystackWebhook()` verifies signature → ignores non-`charge.success` events → converts kobo → credits the matched user. Duplicates return `ack=true, reason=duplicate`; bad signature/unknown user return `ack=false` (Paystack gets a 4xx and will retry).
- `WalletService` unchanged except a fix below enabling fresh wallets.

**Web:**
- `WalletController` — `GET /wallet` (dual balances + last 25 transactions + quick top-up chips), `POST /wallet/topup` (validates ₦100–₦1M, redirects to Paystack `authorization_url`; friendly error when Paystack unconfigured).
- `PaystackWebhookController` — `POST /paystack/webhook`, **CSRF-exempt** (signature is the gate), returns 200 on ack / 400 on reject. Route is public (no `auth`).
- Views: `wallet/index.blade.php`, wallet link added to rider nav.

**Admin (Control Tower):**
- `SubsidyController` — `GET /admin/subsidies` (MDA dashboard: total issued, staff funded, per-workplace subsidy totals, recent subsidy transactions filterable by workplace) and `POST /admin/subsidies/credit` (CSV `email,amount` bulk credit; skips header/unknown emails/bad rows; per-row idempotent references `MDA-{workplace}-{batch}-{index}`; non-CSV rejected). Subsidies link added to admin nav.

**API (`/api/v1`):** `WalletController` — `GET /wallet` (balances + transactions), `POST /wallet/topup` (returns `reference` + `authorization_url`, 503 when unconfigured). Both under `auth:sanctum`.

**Config:** `config/services.php` → `paystack` block (`public_key`, `secret_key`, `webhook_secret`, `mode=test`); `.env.example` documents the three `PAYSTACK_*` keys.

**Fix (latent Sprint 2 bug):** newly created wallets had a `null` `version` in memory (DB default is `1`), so the optimistic-lock `WHERE version = ?` matched nothing and the first mutation threw "Wallet changed concurrently. Please retry." Added model-level `$attributes` defaults on `Wallet` mirroring the DB defaults — this also hardens the existing booking hold flow for passengers with no prior wallet.

**Tests (19 new — 90 total, 269 assertions):** `WalletFundingTest` (charge.success credits cash, idempotent duplicate webhook, invalid signature rejected, non-charge events ignored, malformed reference rejected, unknown user rejected), `SubsidyTest` (non-admin 403, dashboard view, CSV bulk credit happy path, unknown-email/bad-row skip, file required, non-CSV rejected), `WalletTopUpTest` (guest redirect, wallet page balances, unconfigured top-up error, amount validation, API 401, API balances, API top-up 503).

### 4.10 Sprint 4 — GTFS Publisher: Static Feed + GTFS-RT (COMPLETE)

**Schema (3 new migrations):** `gtfs_stops` (unique `stop_id`, `stop_lat`/`stop_lon` decimal:7, indexed `corridor`), `gtfs_routes` (unique `route_id`, unique `corridor`), `gtfs_feed_meta` (single-row generation metadata). Models `GtfsStop`, `GtfsRoute`, `GtfsFeedMeta` added.

**Seeder:** `GtfsStopSeeder` — 53 representative Abuja stops across the three corridors (Kubwa→CBD 20, Nyanya→Idu 18, Lugbe→CBD 15), wired into `DatabaseSeeder`.

**`GtfsService`** — builds the 7 GTFS files (`agency/stops/routes/trips/stop_times/calendar/shapes`) from live scheduled/active trips + the stop catalog and zips them to `storage/app/public/gtfs/gtfs.zip`:
- Routes `updateOrCreate`d per corridor (`KUB-CBD`, `NYY-IDU`, `LUG-CBD`).
- Trip points come from relational `trip_waypoints` rows first, then the JSON snapshot, then corridor endpoints.
- Each point resolves to a catalog stop within `stop_match_radius_m`, else a `SYN-{tripId}-{n}` synthetic stop.
- `stop_times` interpolated from `departure_time` at `avg_speed_kmh`; calendar covers `service_days` (365) from today.
- Records `GtfsFeedMeta` and returns stats.

**`GtfsRtService`** — dependency-free hand-rolled protobuf wire encoder emitting a `FeedMessage` VehiclePositions feed (plus an empty-snapshot TripUpdates feed) for Google Transit partner polling. Field numbers follow the `transit_realtime` schema.

**Wiring:**
- `php artisan gtfs:generate` command (`app/Console/Commands/GenerateGtfsFeed`).
- Nightly `Schedule::command('gtfs:generate')->dailyAt('02:00')` in `routes/console.php`.
- `GenerateGtfsFeedJob` dispatched on every trip publish (`TripService::queueGtfsRegeneration()`).
- Public routes: `GET /gtfs/gtfs.zip`, `GET /gtfs/gtfs-rt/vehicle_positions.pb`, `GET /gtfs/gtfs-rt/trip_updates.pb`.
- Admin dashboard `/admin/gtfs` (feed status, download, regenerate) + "GTFS Publisher" nav link.
- Config block `workride.gtfs.*` (agency, timezone, service_days, avg_speed_kmh, stop_match_radius_m).

**Bugs fixed (found during Sprint 4 hardening):**
- The `waypoints` JSON column on `trips` shadows the `waypoints()` hasMany relation (the cast wins on property access), so `GtfsService::tripPoints()` now reads the relation via its query builder / `getRelation()`, falling back to JSON, then corridor endpoints.
- `corridorEndpoints()` returned an associative array (`start`/`end` keys) while callers index numerically — now returns a plain list.
- `isset($resolved['synthetic'])` matched both `true` and `false`, duplicating every resolved stop into the synthetic list — now gated on the boolean being `true`.
- GTFS-RT field numbers: `FeedEntity.vehicle` used field 8 (shape) instead of 4; `TripDescriptor.start_time` used field 5 (start_date) instead of 4.

**Tests (18 new — 108 total, 339 assertions):** `GtfsServiceTest` (7-file zip, feed metadata, per-corridor routes, relational-vs-JSON waypoint precedence, JSON fallback, synthetic stops, feed-path lifecycle), `GtfsRtTest` (a wire-format decoder proves only active trips with coords are included, vehicle on field 4, start_time on field 4, header version 2.0 + timestamp, empty trip-updates snapshot), `GtfsControllerTest` (public endpoints, 404 before generation, download after, admin dashboard gating, regenerate).

### 4.11 Sprint 5 — Road Sensor + Road Intelligence + Routing Cost Caps (COMPLETE)

**Schema (1 new migration):** `api_cost_logs` — `provider` (osrm/google/mapbox), `service`, `cost_naira` decimal(12,2), `meta` json, indexed `[provider, created_at]` and `[service, created_at]`. Model `ApiCostLog` (decimal:2 + array casts).

**Infrastructure (open-source-first per the Tools Selection Guide):**
- `RoutingService` — strategy chain driven by `config/workride.php` `routing.primary` (default `osrm`, OSRM self-hosted = free): asserts free OSRM host first, falls back to Google Directions, then Mapbox; Google/Mapbox only run when their key is present **and** `withinMonthlyCap()` passes. Every paid call is logged via `CostLogger` → `api_cost_logs` (₦20/Google, ₦25/Mapbox per request). Returns `[distance_m, duration_s, points]`; Google polyline decoded client-side. Throws `RoutingUnavailableException` when no provider can serve.
- `CostLogger` — `log()`, `monthlySpend(?provider)`, `withinMonthlyCap($additional)`, `monthlyCalls(?provider)`; monthly cap from `workride.api_caps.monthly_naira` (default ₦20,000) prevents API-bill surprise in the field.
- `docker-compose.yml` — `redis:7-alpine` on by default; `osrm`, `postgis/postgres:15-3.4`, `ghcr.io/mobilitydata/gtfs-validator`, `metabase` all behind `profiles: ['selfhost']` (future self-host targets, not wired to `.env` yet). `.env.example` documents the `OSRM_*`, `GOOGLE_MAPS_*`, `MAPBOX_*`, `WORKRIDE_API_MONTHLY_CAP`, and road-sensor toggles.

**`RoadIntelligenceService`** — World Bank RoadLab method:
- `recordEvent()` persists one sensor reading, runs confirmation clustering, refreshes the event, then updates its road segment when confirmed.
- `confirmClusters()` — groups unconfirmed events of the same type within `pothole_confirm.radius_m` (20 m) and `within_hours` (72 h); any cluster of `min_reports` (5) is confirmed (`is_confirmed = true`), then each confirmed pothole refreshes its `road_segments` row.
- `iri($z, $speed)` = `alpha * sqrt(z²) / speed + beta` (alpha 2.0, beta 1.5 from `road_sensor.*`), mapped via `conditionFor()` to the existing `iri_thresholds` bands (Excellent <4, Good <6, Fair <10, Poor ≥10).
- `refreshSegment()`, `confirmedPotholes(?hours)`, `segmentsByCondition()`, `fermaExport()` (CSV rows: road_name, lat, lng, type, severity, reported_at).

**API + Web:**
- `POST /api/v1/road-events` (Sanctum) — `RoadSensorController::store` validates type via `Rule::enum(RoadEventType::class)`, severity 1–5, speed, accelerometer_z; rejects points outside the FCT bounding box (422, "Road events can only be collected inside the FCT."). Returns 201 with the persisted event.
- `GET /api/v1/road-events` (public) — confirmed potholes only, optional `hours` filter, anonymized (no `user_id`).
- `GET /road/map` (public, `RoadMapController`) — Leaflet heatmap with severity-colored dots (≥4 red, 3 gold, else green) + worst-segments IRI table. Uses new guest-safe `layouts/public` (the app layout assumes `auth()->user()`).
- `GET /admin/road` + `GET /admin/road/export` — Ops dashboard (total/unconfirmed/confirmed potholes, segments, condition breakdown) + FERMA-ready CSV download.

**Frontend:**
- `use-road-sensor.js` — Alpine `roadSensor` component: listens to `DeviceMotionEvent`, flags hits where Z-acceleration > `road_sensor.pothole_z_threshold` (15), throttles to one report / 30 s, reads `navigator.geolocation`, POSTs to the API. Wired into `trips/show` for the active driver.
- `road-map.js` — Leaflet + OSM tiles (`window.initRoadMap`); added as a second Vite input.
- Nav links "Road Map" (rider) and "Road Intelligence" (Control Tower).

**Config:** `config/workride.php` — `routing.*` (primary, osrm_host, osrm_timeout, use_google_fallback, google_api_key, google_cost_per_request 20, use_mapbox_premium, mapbox_access_token, mapbox_cost_per_request 25), `api_caps.monthly_naira` (20000), `road_sensor.*` (pothole_z_threshold 15, iri_alpha 2.0, iri_beta 1.5, max_speed_kmh 200).

**Bugs fixed (found during Sprint 5 hardening):**
- Freshly-created `RoadEvent` models had `is_confirmed` = `null` in memory (DB default `false` is only applied on insert), so `recordEvent()` returned a stale event and the `refreshSegment()` branch never fired. Added model-level `$attributes` defaults (`severity` 1, `is_confirmed` false) plus `$event->refresh()` after clustering.
- `RoadEvent` is confirmed in the DB by `confirmClusters()` but the returned model wasn't refreshed — the same stale-value bug.
- The public `/road/map` page 500'd for guests because `layouts/app` reads `auth()->user()` directly. New guest-safe `layouts/public` used instead.

**Tests (26 new — 134 total, 409 assertions):** `RoadSensorTest` (unauth 401, verified driver 201 + DB row, outside-FCT 422, invalid type 422, public endpoint returns only confirmed + no user_id, public map renders), `RoadIntelligenceServiceTest` (5-within-radius confirms all, <5 not confirmed, far-apart no cluster, >72h excluded, IRI→condition bands, null-z IRI, confirmed potholes refresh segment IRI, recordEvent confirms + writes segment, FERMA export filter), `RoutingServiceTest` (OSRM path via Http::fake, Google fallback when OSRM empty, mapbox, cost-logging, monthly cap block), `RoadAdminTest` (dashboard gating, admin view, FERMA CSV content, non-admin export 403).

### 4.12 Sprint 6 — PWA Award UI + Impact Certificates + Impact Analytics + Demo Users (COMPLETE)

**`Co2Service` (`app/Services/Co2Service.php`)** — guide-formula impact math:
- `co2Kg(occupants, distanceKm)` = `(occupants - 1) * distance * co2_per_passenger_km` (0.12), 0 for a solo driver.
- `treesEquivalent(co2Kg)` = `co2 / trees_per_kg_co2` (21); `fuelLitres(occupants, distanceKm)` = `distance * fuel_litres_per_km * occupants` (0.10).
- `forRide(occupants, distanceKm)` → snapshot; `distanceKm()` via `GeofenceService::haversine()`.

**`CalculateImpactJob` (`app/Jobs/CalculateImpactJob.php`)** — dispatched from `TripService::completeTrip()` (after `TripCompleted`): loads the trip, gathers `boarded`/`completed` bookings, computes per-participant CO₂/trees/fuel from the waypoint path distance (corridor fallback from new `workride.corridor_distance_km`), upserts each participant's `ImpactStat` (`credit()` helper). Solo trips credit nothing.

**Impact pages (rider PWA):**
- `ImpactController` — `GET /impact` (auth): personal CO₂/fuel/trips/tree cards + Abuja-wide top-25 leaderboard + workplace leaderboard (filtered to the signed-in user's workplace).
- `ImpactCertificateController` — `GET /impact/certificate/{co2|fuel}` (auth): printable certificate sheet (CO₂ SAVED / FUEL SAVED) with shared rides, savings, Abuja rank, green percentile, Green Level, and a **QR code** (`simplesoftwareio/simple-qrcode`, SVG data-URI) that decodes to the public verify URL. `GET /impact/verify/{user}/{type}` (public, guest-safe `layouts/public`) confirms the record — the anti-fraud audit trail for CSR/ESG + subsidy claims.

**PWA shell:**
- `PwaController` — `GET /manifest.json` (Web App Manifest: standalone, theme `#2E7D32`, background `#F6F9F6`, 192/512 icons) + `GET /sw.js` (service worker: `workride-v1` shell cache, stale-while-revalidate, activate cleanup, skipWaiting).
- `resources/js/app.js` — SW registration + `beforeinstallprompt` → `window.deferredInstallPrompt` / `wr-install-ready` custom event (for a future install button).
- Icons `public/pwa/icon-192.png` + `icon-512.png` (generated). Manifest `<link>` + `theme-color` + apple-touch-icon added to `layouts/app` + `layouts/public`.

**Demo users (`DemoUserSeeder`)** — funding-pitch accounts, all password `demo1234` (config `workride.demo.password`), attached to FMF:
- `driver@workride.ng` — Aisha Bello, L3 paid driver, Toyota Hiace Coaster ABJ-849-KJ, ₦12,450 wallet, 42-trip impact (756 kg CO₂).
- `volunteer@workride.ng` — Chinedu Okafor, workplace-verified volunteer, 15-trip impact.
- `passenger@workride.ng` — Fatima Yusuf, L1 with ₦3,200 cash + ₦15,000 subsidy credits, 28-trip impact.

**Config:** `workride.demo.password`, `workride.corridor_distance_km` (kubwa_cbd 22, nyanya_idu 14, lugbe_cbd 12). Composer: `simplesoftwareio/simple-qrcode` added (SVG QR needs no GD).

**Bugs fixed (found during Sprint 6 hardening):**
- `DemoUserSeeder` originally passed nested `wallet`/`vehicle`/`impact` arrays into `User::updateOrCreate` → `SQLSTATE[42S22] Unknown column 'wallet'`. Now strips the nested keys first and applies wallet/vehicle/impact via their own `updateOrCreate` calls.
- PWA manifest asserted as `application/json` and relative paths in tests — controller returns `application/manifest+json` with absolute `start_url`/`scope`/icon URLs; tests updated to match.
- PWA icons can't be routed through the feature-test HTTP client (static files, no route) — replaced the HTTP assertion with an on-disk `assertFileExists` + size check.

**Tests (25 new — 159 total, 476 assertions):** `Co2ServiceTest` (solo = 0, 2-occupant formula, trees factor, fuel litres, forRide snapshot, haversine distance), `CalculateImpactJobTest` (driver + riders credited, each boarded passenger, solo no-op, cancelled excluded, missing trip no-op), `ImpactPageTest` (auth redirect, personal stats render, workplace leaderboard, cert auth + CO₂/Fuel render with QR, invalid type 404, public verify confirms/empty), `PwaControllerTest` (manifest JSON + icons, service worker JS, icons on disk), `DemoUserSeederTest` (3 users with expected levels/plate/subsidy/impact).

### 4.13 Sprint 3.5 — Time-Bank (Ride Credits) + Earned Wallet + P2P Transfers + Payouts (COMPLETE)

**Schema (5 migrations):**
- `add_earned_balance_to_wallets_table` — `earned_balance` decimal(15,2) default 0 after `subsidy_credits`.
- `add_overdue_flag_to_users_table` — `has_overdue_ride_credit` boolean default false.
- `create_ride_credits_table` — `user_id`, `trip_id`/`booking_id` (nullable FKs), `seats_owed`, `seats_repaid`, `fare_value`, `due_date`, `status` (owed/repaid/overdue/waived), indexed `[user_id, status]`.
- `create_p2p_transfers_table` — `sender_wallet_id`, `receiver_user_id`, `amount`, `fee`, `type`, `reference` (unique), `status`, `meta`.
- `create_payouts_table` — `wallet_id`, `amount`, `bank_code`, `account_number`, `status`, `reference` (unique), `meta`.

**Enums (4 new + 2 extended):** `RideCreditStatus`, `P2pTransferType`, `P2pTransferStatus`, `PayoutStatus`; `PaymentMethod::RideCredit`, `TransactionType` cases `Earned`, `P2pDebit`, `P2pCredit`, `Fee`, `Payout`.

**Models:** `RideCredit` (enum casts + `outstandingSeats()`/`isSettled()`), `P2pTransfer` (`senderWallet`/`receiver` relations), `Payout`; `Wallet` gains `earned_balance` (fillable/attributes/casts) + `payouts()`; `User` gains `has_overdue_ride_credit` cast + `rideCredits()`/`receivedTransfers()`.

**Services:**
- `WalletService` (rewritten) — triple-balance: `creditEarned()`, `holdForBooking()` (subsidy → earned → cash priority), proportional `restore()` on refunds (uses hold `meta` breakdown), idempotent `logCashCollection()` (`BOOK-{id}-CASH`), version-checked `debitForTransfer()`/`creditForTransfer()` (P2P + payouts, caller holds `FOR UPDATE`).
- `RideCreditService` — `seatsFor()` = `ceil(fare / avg_fare_per_seat)` (600), `assertEligible()` (L2+ NIN, registered vehicle, no overdue credit, ≤ `max_owed_seats` 3 at once), `createOwedRide()`, `repayWithDrive()` (oldest open credit, 1 seat per passenger carried on trip completion), `cancelRideCredit()` (waive on cancel/no-show), `hasOverdueCredit()` + `flagOverdue()`.
- `P2pTransferService` — `transfer()` inside a DB transaction with `SELECT ... FOR UPDATE` on both wallets; 1% cash fee (min ₦10) vs free earned; daily limit ₦10k; L2+ sender above ₦5k; receiver must be L1+; subsidy NEVER transferable; idempotent `P2P-{sender}-{ts}-{rand}` refs with `-DEBIT`/`-CREDIT`/`-FEE` transactions + `P2pTransferCompleted` event.
- `PayoutService` — `withdraw()` debits earned-first then cash (never subsidy), min ₦100 / max ₦100k, mock Moniepoint payout ledger (`PO-{user}-{rand}`, settles to completed).
- `PricingService::driverEarning()` now subtracts commission (10%) + `unionFee` (5%) + insurance (₦100).

**Booking/Trip wiring:** `BookingService::book()` accepts `payment_method=ride_credit` (fare_paid 0, no hold, `createOwedRide`), cancel/no-show waives the credit, `settle()` credits `EARN-{bookingId}` when Time-Bank is on; `TripService::completeTrip()` calls `settle()` + `repayWithDrive()` per carried passenger.

**API (`/api/v1`):** `POST /wallet/transfer`, `GET /wallet/transfers`, `POST /wallet/withdraw`, `GET /ride-credits`; wallet index now returns `earned_balance`.

**Web:** Wallet page now shows 3 balance cards, quick top-up, "Send money" (cash/earned), "Withdraw to bank", a Ride Credits (Time-Bank) panel, and transfers/payouts history; `trips/show` booking select gains `ride_credit` when the feature is enabled for L2+ users.

**Config:** `workride.time_bank.*` (enabled/avg_fare_per_seat/due_days/max_owed_seats), `workride.p2p.*` (daily_limit/sender_level_threshold_amount/fee_cash_rate/fee_cash_min), `workride.payout.*` (min/max amount), `workride.union_fee_rate`; all mirrored in `.env.example` (`FEATURE_TIME_BANK` gates the whole feature).

**Bugs fixed (found during Sprint 3.5 hardening):**
- `assertEligible()` relied on the persisted `has_overdue_ride_credit` flag, which `flagOverdue()` set *inside* the booking DB transaction — when the booking then threw, the whole transaction (flag + status) rolled back, so the flag never survived a rejected request. Now the overdue gate reads the committed `ride_credits` rows directly (`hasOverdueCredit()`), with the user flag updated as a best-effort cache.

**Tests (25 new — 184 total, 561 assertions):** `RideCreditTest` (disabled gate, NIN required, vehicle required, owed-seats booking with no hold, max-owed-seats cap, cancel waives, overdue blocks, trip completion repays a seat, API index), `EarnedWalletTest` (earning = fare − commission − union − insurance on capture, idempotent double-settle, disabled no-op, payout earned-first then cash, subsidy never withdrawable, min amount, API earned balance + withdraw validation), `P2pTransferTest` (disabled gate, cash fee, earned free, receiver L1+, sender L2+ over threshold, daily limit, subsidy never transferable, history).

### 4.14 Sprint 7 — Business Dashboard + Receipts + Exports (COMPLETE)

**Business Controller (`app/Http/Controllers/Admin/BusinessController.php`)** — the Control Tower "Business" page (per guide §8 dashboard + §11 receipts):
- `index()` aggregates: gross revenue (sum of captured/fare-bearing bookings + cash fares), MRR (last 30 days), driver earnings, platform commission, union fees, insurance collected, P2P fees, payouts (net cash moved out), subsidy issued/spent/remaining, cash/earned held, `cash_collected_log`, paid rides; plus `revenueByDay()` (last 14 days), `tripsByCorridor()`, `subsidyByWorkplace()` (issued vs spent + utilization).
- CSV exports via a private `csv()` helper (`text/csv` + attachment disposition): `exportTransactions()` (reference, date, user, email, type, amount, description), `exportSettlements()` (per-driver totals from `Transaction` `meta->>"$.fare"`), `exportSubsidy()` (workplace, staff funded, issued, spent, utilisation).

**Routes (`routes/web.php`):** `admin.business.index`, `admin.business.export.transactions|settlements|subsidy` in the admin group; "Business" sidebar link added to `layouts/admin.blade.php`.

**View (`resources/views/admin/business.blade.php`):** KPI cards, inline-SVG revenue-per-day bar chart (no CSS-class/JS chart dependency), corridor revenue list, subsidy per-workplace table with utilization pill.

**`ReceiptController` (`app/Http/Controllers/Web/ReceiptController.php`)** — 5 printable financial receipts (guide §14 types 1,2,3,4,8; the CO₂/Fuel certificates and FERMA road report already ship), all QR-verifiable:
- `booking(Booking)` — Trip Booking Receipt (`BK-{id}`), passenger/driver/admin.
- `earnings(Booking)` — Driver Earnings Receipt (`EARN-{bookingId}`) with commission/union/insurance breakdown via `PricingService`.
- `topup(Transaction)` — Wallet Top-up Receipt (Paystack-funded, `TOPUP-{txRef}`), wallet owner/admin.
- `subsidy(Transaction)` — Subsidy Credit Receipt (MDA audit trail), admin-only.
- `statement(Request, string $month)` — Monthly Commute Statement (`ST-{userId}-{Y-m}`) with per-payment-method totals + ride log; admins can pass `?user=`.
- `verify(type, reference)` — public QR target resolving `BK-`/`EARN-`/`TOPUP-`/subsidy-reference/`ST-` refs → `receipts.verify` view (guest-safe `layouts.public`), with a `verified` flag (confirmed/boarded/completed for bookings, EARN transaction exists for earnings).
- Private `render()` builds `verifyUrl` + SVG QR data-URI (`SimpleSoftwareIO\QrCode`) + issued-at timestamp; layout requires `holder` (passenger/driver/user name) for the sheet header.

**Views (`resources/views/receipts/`):** shared printable `layout.blade.php` (branded sheet, QR footer, print/`@media print` button hiding, `WORKRIDE VERIFIED` stamp) + `booking/earnings/topup/subsidy/statement/verify`.

**UI wiring:** receipt link per paid booking in `bookings/index.blade.php`; "Earnings receipt" for boarded/completed paid bookings in `trips/show.blade.php`; "Monthly statement →" in the wallet recent-transactions header; per-topup receipt link in wallet rows; the `MDA-…` subsidy reference in `admin/subsidies.blade.php` now links to the subsidy receipt.

**Routes:** public `GET /receipts/verify/{type}/{reference}` + auth group `receipts.booking/{booking}`, `receipts.earnings/{booking}`, `receipts.topup/{transaction}`, `receipts.subsidy/{transaction}`, `receipts.statement/{month}`.

**Bugs fixed (found during Sprint 7 hardening):**
- The shared receipt layout referenced `$holder` (line 103) but no printable-render path supplied it → `Undefined variable $holder` view exception. Now every `render()` call passes `holder` (passenger/driver/user name).
- `verifyBooking()` used `in_array($booking->status, [...->value], true)` — the cast `status` is a `BookingStatus` enum, so the strict comparison against string values always failed and the verify page showed "no payment yet". Now compares against `BookingStatus` enum instances.

**Tests (25 new — 209 total, 638 assertions):** `BusinessDashboardTest` (admin-only guard, dashboard render, gross-revenue aggregation excluding free rides, earnings + subsidy KPIs, payouts KPI, 3 CSV export content/headers, non-admin export 403), `ReceiptTest` (guest redirect, passenger/driver own-booking receipt + stranger 403, driver earnings receipt + non-driver 403, top-up receipt owner + stranger 403, subsidy receipt admin + non-admin 403, own monthly statement, invalid month 422, public verify for booking/top-up/statement refs, unknown/bogus reference 404).

### 4.15 Sprint 8 — Employer Mobility + Rewards & Green Points + Commodity Commerce (COMPLETE)

**Schema (8 migrations, all feature-gated by config):**
- `employers` — name, slug, email, phone, address, program_type (`full`/`one_way`/`percent`/`capped`), coverage_pct (for percent), cap_per_ride_ngn (for capped), active, approved.
- `employer_members` — employer_id, user_id, employee_id, status (`active`/`suspended`), joined_at; unique `[employer_id, user_id]`.
- `add_employer_columns_to_bookings` — `employer_id` (nullable FK), `employer_coverage_ngn`, `employer_coverage` enum (`full`/`one_way`/`percent`/`capped`, cast `EmployerCoverageType`).
- `reward_campaigns` — name, description, audience (`drivers`/`passengers`/`volunteers`/`both`), trigger (`trip_completed`/`volunteer_ride`/`weekly_five_rides`/`monthly_ten_rides`/`pothole_confirmed`), reward_type (`cash`/`earned`/`subsidy`/`green_points`), reward_value, period (`once`/`daily`/`weekly`/`monthly`/`unlimited`), budget_total/budget_spent, sponsor_type/sponsor_name, starts_at/ends_at, active, created_by.
- `reward_claims` — user_id, campaign_id, trigger, reward_type, reward_value, reference (unique), period_key, meta, awarded_at.
- `add_green_points_to_users` — `green_points` int default 0.
- `commodities` — symbol (unique), name, category (`precious_metal`/`agricultural`/`fuel`), unit (gram/kg/bag/litre), current_price_ngn, is_tradable, is_shop_item, active.
- `commodity_positions` — user_id, commodity_id, quantity, avg_cost_ngn; unique `[user_id, commodity_id]`.
- `shop_orders` — user_id, reference (unique), items json, total_ngn, paid_from (`cash`/`earned`), status (`placed`/`fulfilled`/`cancelled`), meta, fulfilled_at.

**Enums (12 new + 2 extended):** `EmployerProgramType`, `EmployerCoverageType`, `EmployerMemberStatus`, `EmployerTransactionType` (funding/cover/refund), `RewardAudience`, `RewardTrigger`, `RewardPeriod`, `RewardType` (cash/earned/subsidy/green_points), `SponsorType`, `CommodityCategory`, `OrderPaymentSource` (cash/earned), `OrderStatus`; `TransactionType` gains `CommodityBuy`, `CommoditySell`, `Purchase`, `OrderRefund`; `UserRole` gains `EmployerAdmin` + `isPassenger()` and `assignableCases()` excludes employer_admin.

**Models (9 new + updates):** `Employer` (route-bound via `employer_id`, with `wallet()`, `members()`, `transactions()`, `isProgramRunning()`), `EmployerWallet` (balance + version, optimistic lock), `EmployerTransaction`, `EmployerMember`, `RewardCampaign` (`claims()` FK fixed to `campaign_id`, `isRunningNow()`, `hasBudget()`), `RewardClaim` (`campaign()` FK fixed to `campaign_id`), `Commodity` (`unitLabel()`, `currentValue()`), `CommodityPosition` (`currentValue()`), `ShopOrder` (enum casts). `Booking` gains `employer()` relation + `passengerHoldAmount()`; `User` gains `green_points` + `employers()`/`memberships()` relations; `ActivityLog` gains a static `log()` helper.

**Services:**
- `EmployerLedgerService` — triple-ledger wallet (`balance`, versioned) with `fund()` (idempotent `FUND-{employer}-{ref}`), `cover()` (COVER at boarding, `EMP-{booking}-COVER` reference), `refund()` (only when a COVER transaction exists — a confirmed-then-cancelled booking has nothing to refund), and `transactions()`.
- `EmployerService` — `assertEnabled()`, `programFor()`, `coverageFor(booking)` → `[coverage_ngn, type]` from the employer program (full covers the passenger's hold, one-way covers a configurable 50%, percent applies `coverage_pct`, capped applies `cap_per_ride_ngn`), `enroll()`, `members()`, `fund()` passthrough.
- `RewardService` — sponsor/engine auto-award: `award(user, trigger)` runs every campaign matching trigger + audience + `isRunningNow()` + `hasBudget()`, applies `period` dedupe via `period_key` + unique `reference`, mints cash/earned/subsidy/green-points credits, marks budget_spent, writes `ActivityLog`; `redeemGreenPoints(user, points)` → naira via `green_points_naira_per_point` (min `green_points_min_redeem`), mints an Earned transaction.
- `CommodityService` — wallet-to-goods commerce, **subsidy NEVER spendable**: `buy()` (cash→earned split via `debitForTransfer`, upserts `CommodityPosition` with weighted average cost), `sell()` (proceeds to cash, `creditForTransfer`, partial sell reduces / full sell deletes position), `portfolio()`, `placeOrder()` (shop items only, `OrderPaymentSource::Cash`|`Earned`, `Purchase` transaction, items JSON), `cancelOrder()` (owner-only, placed-only, full refund via `OrderRefund`), `fulfillOrder()`.

**Integration (Sprint 8 wired into existing flows):**
- `BookingService::book()` stores `employer_id`/`employer_coverage_ngn`/`employer_coverage` (via `EmployerService::coverageFor`) when the passenger belongs to an active program; `cancelBooking()` refunds any employer coverage back to the employer wallet; `board()` runs `coverPartial` when employer coverage is present; `settle()` covers the full employer amount at boarding.
- `WalletService::passengerHoldAmount()` gives the hold split; booking holds are reduced by employer coverage so passengers never double-pay.
- `TripService::completeTrip()` fires ride/volunteer/streak rewards (weekly-five / monthly-ten via `RewardTrigger`), `RoadIntelligenceService` fires the `pothole_confirmed` reward.

**Controllers + routes:**
- `Admin\EmployerController` — index/create/store/show/fund/enroll (+ CSV bulk enroll parse). `Admin\RewardController` — index/create/store/toggle.
- `Web\RewardsController` — `/rewards` index + `/rewards/redeem` (Green Points → earned balance). `Web\CommodityController` — `/commodities` market + buy/sell. `Web\ShopController` — `/shop` orders + store + cancel.
- Admin sidebar gains "Employers" + "Rewards"; rider nav gains "Rewards", "Commodities", "Shop".

**Views:** `admin/employers/index|create|show`, `admin/rewards/index|create`, `rewards/index` (campaigns + green points + redeem), `commodities/index` (market + portfolio), `shop/index` (items + orders + QR note).

**Config (`config/workride.php`):** `employer_programs.enabled` (`FEATURE_EMPLOYER_PROGRAMS`), `rewards.*` (`FEATURE_REWARDS`, volunteer_green_points 10, green_points_naira_per_point 5, green_points_min_redeem 50), `commodities.enabled` (`FEATURE_COMMODITIES`).

**Seeder:** `Sprint8DemoSeeder` (FMF employer + funded ₦2,000,000 wallet + 3 enrolled staff, 2 reward campaigns, 4 commodities — Gold/Rice/Maize/Fuel), wired into `DatabaseSeeder`.

**Bugs fixed (found during Sprint 8 hardening):**
- `RewardClaim::campaign()` inferred the FK `reward_campaign_id` while the column is `campaign_id` → `admin/rewards` 500'd (`no such column: reward_claims.reward_campaign_id`). Fixed both directions: `campaign()` on `RewardClaim` and `claims()` on `RewardCampaign` now pass `'campaign_id'` explicitly.
- `Admin\RewardController::store()` validated `type`/`value` but only mapped `value` → `reward_value`, dropping `reward_type` (NOT NULL) → `Integrity constraint violation: reward_campaigns.reward_type`. Now maps `reward_type` too.
- `EmployerLedgerService::refund()` refunded unconditionally; a booking cancelled while still confirmed has no COVER transaction, so the refund threw. Now refunds only when an `EMP-{booking}-COVER` transaction exists.

**Tests (46 new — 255 total, 765 assertions):** `EmployerTest` (11 — feature-gated, program coverage full/percent/capped, enroll, cover-on-board, cancel-refund, funding, CSV enroll, admin flows), `RewardTest` (14 — disabled no-award, cash/earned/green-points campaigns, budget exhaustion, period dedupe once/weekly, ended campaign skip, redeem + min threshold + over-balance, admin create/toggle), `CommodityCommerceTest` (21 — disabled gate, buy cash/earned, cash→earned split, subsidy never spendable, insufficient funds, inactive/non-tradable, sell partial/full/over-sell, orders cash/earned, subsidy order blocked, inactive item, cancel + foreign/closed cancel, fulfill, portfolio, web market/shop render, web buy/sell/order/cancel flows, validation).

### 4.16 Sprint 9 — Missions + Global Nav Redesign (COMPLETE)

**Missions: sponsor-defined promoted activities with automatic reward payout.**

**Schema (3 migrations):** `missions` (name, unique `slug`, description, `sponsor_type`, `activity_type`, `metric_goal`, `metric_window_days`, `reward_type`, `reward_value`, `verification_mode` auto/proof, `status` draft/published/paused/completed, sponsor fields, starts_at/ends_at, created_by), `mission_progress` (unique `[user_id, mission_id]`, `current_metric`, `goal_metric`, `status` active/completed/awarded/expired), `mission_submissions` (mission_id, user_id, proof photo on `public` disk `mission-proofs`, lat/lng, note, `reward_awarded` flag).

**Enums (5 new):** `MissionActivityType` (volunteer_rides, paid_rides, peak_hour_rides, passenger_rides, pothole_reports, potholes_confirmed, custom), `MissionVerificationMode` (auto/proof), `MissionStatus` (draft/published/paused/completed), `MissionProgressStatus` (active/completed/awarded/expired), `MissionSubmissionStatus` (pending/approved/rejected).

**Models (3):** `Mission` (`sponsorType`/`activityType`/`rewardType`/`verificationMode`/`status` enum casts + `isRunningNow()` + `budgetAt()` + `currentCountFor()`), `MissionProgress` (`mission()`/`user()` relations, unique pair), `MissionSubmission` (`reward_awarded` + status casts).

**`MissionService` (`app/Services/MissionService.php`)** — observation + payout engine:
- `recordActivity(MissionActivityType, User, int $qty = 1)` — runs all published, running, correct-activity missions, `lockForUpdate` on the progress row, increments `current_metric` (capped at goal), completes → `creditReward()`.
- `creditReward()` — idempotent payout keyed on `MIS-{mission}-{user}` / `MIS-PROOF-{mission}-{user}`: Cash → `creditCash`, Earned → `creditEarned`, Subsidy → `creditSubsidy`, GreenPoints → `user->increment('green_points')`; `ActivityLog::log` on both.
- `submitProof(User, Mission, data)` — proof-mode missions only; photo on `public` disk `mission-proofs`, lat/lng/note stored, status pending.
- `approveProof()` / `rejectProof()` — approve credits reward + marks submission awarded; reject no-op.
- `activeFor(User)` — running missions with progress/status for the rider hub; `myAwards(User)` — awarded mission completions.

**Event-flow wiring (auto-verify):**
- `TripService::completeTrip()` (now construct-injected with `MissionService`) fires `volunteer_rides`/`paid_rides` (driver) and `passenger_rides`/`peak_hour_rides` (each boarded/completed booking, peak = 6–9 & 16–19 local hour via new `isPeakHour()` helper) as riders are counted.
- `RoadIntelligenceService::recordEvent()` fires `pothole_reports` (every event) + `potholes_confirmed` (when `is_confirmed && type=Pothole`).

**Controllers + routes:**
- `Web\MissionController` — `GET /missions` rider hub (live missions cards with progress bars, proof-form toggle, my awards).
- `Admin\MissionController` — `GET/POST /admin/missions` index/create/store, `GET /admin/missions/{mission}` show, `POST .../toggle`, `POST .../submissions/{submission}/approve|reject`.
- Routes `missions.*` (auth) + `admin.missions.*` (admin group); admin sidebar gains "Missions".

**Views:** `missions/index.blade.php` (mission cards, proof photo form), `admin/missions/{index,create,show}.blade.php` (Control Tower — create form has a `verification_mode` JS-hints toggle row).

**Config:** `config/workride.php` → `workride.missions.enabled` (`FEATURE_MISSIONS` env, default false); `.env.example` documents the flag.

**Seeder:** `DemoMissionSeeder` (pothole-weather brief + sample rewards), gate-guarded (no-ops when disabled), wired into `DatabaseSeeder` after `Sprint8DemoSeeder`. Sponsor types are ONLY `government`/`private`/`community`.

**Global nav redesign (branding):**
- Rewritten `layouts/app.blade.php`: top nav 5 destinations, ⌘K command palette (`resources/js/command-palette.js`, Alpine component in `components/command-palette.blade.php`), profile dropdown (`components/profile-menu.blade.php`), mobile bottom tab bar (`components/mobile-nav.blade.php`), brand mark `components/matching-anim.blade.php` (searching-algorithm SVG: radar scan + route probes + gold winner path, used on landing/dashboard/trips board).

**Bugs fixed (found during Sprint 9 hardening):**
- `MatchingAnim` probes mixed associative `$passenger` points with plain numeric-list points; `$a['x']` blew up on the numeric entries (`Undefined array key "x"`, 500 on `/`). Every probe's `$a` is now an associative `['x' =>, 'y' =>]` point.
- `MissionsTest::mission()` used a static slug → UNIQUE constraint violation when a test created two missions. Now unique per call (`give-free-rides-{random}`).
- Dynamic `:name`/`:class` on `<x-icon>` compiles as PHP and 500s — command palette icon fixed to static `name="search"`, rotation/arrows wrapped in Alpine `:class` `<span>`s (`command-palette` + `profile-menu` components).

**Tests (19 new — 274 total, 819 assertions):** `MissionsTest` — gate off blocks hub + reward; auto-record volunteer rides awards cash once; no award when gate off; proof-mode submit requires photo + note; admin approve credits + marks awarded; reject no-op; duplicate approve idempotent; not-running/ended mission skipped; per-user progress isolation; rider hub renders live missions (name + reward); admin index/create/store/toggle; proof submission persisted; peak-hour + pothole triggers; rewards land in wallet balances.

### 4.17 Sprint 3.6 — Tiered KYC: Open Liveness + NIMC Lookup + Driver Anti-Spoof (COMPLETE)

Adopted from `WORKRIDE-PROMPT-ID-VERIFICATION-LIVENESS.md` (reviewed + corrected). The proposal's 3-tier model maps 1:1 onto the existing `VerificationLevel` (workplace=1, nin=2, driver=3). Corrections applied on adoption: client liveness is treated as a **signal, not a gate** (low score → `pending_manual_review`, never hard-fail); the `face_embedding_hash` idea (hash destroys comparability) was dropped; the Tier-2 frontend-hash theater was replaced with the honest relay (raw NIN → licensed partner over TLS only); the proposed duplicate `api_cost_logs` table was **not** re-created — the existing Sprint 5 table was extended instead; and the unverifiable client-side "tests" (tablet-video rejection etc.) were replaced with server-contract tests.

**Schema (3 migrations):**
- `verifications` gains `liveness_score`, `face_match_score`, `provider` (open/identitypass/smile/dojah), `tier` (1/2/3), `nimc_reference`, `selfie_path`, `selfie_retention_expires_at`.
- `verification_attempts` — one row per KYC attempt (user_id, tier, provider, liveness_score, status, ip_address) driving the 5/day rate limit + audit trail.
- `api_cost_logs` gains `user_id`, `purpose`, `reference` (unique) — idempotent per-user KYC cost ledger.
- `config/filesystems.php` gains an explicit non-public `private` disk (`storage/app/private`).

**Enums (2 new):** `VerificationProvider`, `VerificationTier`.

**Models:** `VerificationAttempt`; `Verification` gains the new fillables + casts + `decryptedSelfie()` (Crypt-decrypt for reviewers); `ApiCostLog` gains `user_id`/`purpose`/`reference` + `user()`; `User` gains `verificationAttempts()`.

**Services:**
- `VerificationService` — `submitTier1()` (open staff-ID liveness: pass → auto-approve Level 1, low score → `pending_manual_review`), `assertWithinAttemptLimit()` (5/day/tier → `VerificationThrottledException`), `recordAttempt()`, `storeSelfie()`/`storeSelfieFile()`/`storeEncrypted()` (base64/file → Crypt-encrypted bytes on the private disk).
- `NimcVerificationService` — Tier-2 NIN lookup: idempotent (same NIN re-submission never re-pays), global + per-provider caps checked *before* the call, raw NIN relayed only to the licensed partner, hash + last4 + partner ref stored, every call cost-logged (`identitypass`/`nin_check`), fail-safe → `pending_manual_review` on unconfigured/unreachable/cap-exhausted.
- `SmileIdService` — Tier-3 driver anti-spoof: `start()` records the pending Level-3 job; `handleWebhook()` resolves it, HMAC-SHA256 signature is the only gate, anti-spoof score must clear `SMILE_ANTI_SPOOF_THRESHOLD`, cost logged on resolution.
- `DeleteExpiredSelfiesJob` — nightly NDPR retention purge of selfies past `WORKRIDE_SELFIE_RETENTION_DAYS` (30).

**API (`/api/v1`):** `GET /verifications/status`, `POST /verifications/tier1|tier2|tier3` (feature-gated on `FEATURE_LIVENESS`, 403 otherwise), public `POST /webhooks/smile` (signature-verified, Paystack-webhook contract).

**Admin Control Tower:** verifications page gains a "Needs review" (`pending_manual_review`) queue chip, provider/tier/liveness-score badges (≥80 green, 75–79 gold, <75 red), a per-provider monthly cost summary (IdentityPass + Smile), and approve/reject actions now available on `pending_manual_review` rows.

**Config:** `workride.verification.*` (enabled/attempts_per_day/liveness_min_score/selfie_retention_days/driver_verification_fee_naira), `services.identitypass.*`, `services.smile.*`; `.env.example` documents `FEATURE_LIVENESS`, `USE_IDENTITYPASS`, `USE_SMILE` and their tunables. `phpunit.xml` gains an `APP_KEY` (encryption-dependent tests now deterministic).

**Bugs found & fixed during hardening:** the API gate read `workride.verification.liveness_enabled` while the config key is `enabled` (tests caught it as 403).

**Tests (16 new — 290 total, 896 assertions):** `TieredVerificationTest` (13 — gate-off 403, tier1 auto-approve + encrypted-selfie round-trip, tier1 low-score manual review, tier1 2/day rate limit, tier2 IdentityPass approve + hash-only + cost log + level 2, not-found reject, unconfigured fallback, cap-exhausted no-call, same-NIN idempotency no second call, tier3 start, Smile webhook invalid-signature 400 / pass-approve / low-score reject, status endpoint), `SelfieRetentionTest` (2 — expired selfie purged, within-window kept).

### 4.18 Investor-Guide Adoptions A–F — Trust, Safety, Inclusivity, Offline, Design Tokens, Landing KPIs (COMPLETE)

Six adoptions from the investor guide review, committed as one feature set (`v0.10.0`). All read from the existing trust/safety/design foundations; no schema redo, no full UI redesign, no hard sort, no FCM.

**A. Mutual ride ratings + driver scoreboard**
- Schema: `ride_ratings` — `booking_id` FK cascade, `trip_id` FK cascade, `rater_id`, `ratee_id`, `rating` (1–5 unsignedTinyInteger), `note`, unique `[booking_id, rater_id]`, index `[ratee_id, created_at]`.
- `RatingService::rate(User, Booking, data)` — resolves the *other party* (passenger rates driver, driver rates passenger, strangers/admins rejected), requires `booking.status` Completed/Boarded **and** `trip.status` Completed, records `RideRating` + `ActivityLog::log('rated_ride', …)` in one transaction. Idempotent: a pre-insert `exists()` check plus a tolerant `QueryException` catch for both MySQL (23000) and SQLite (19) unique violations → "already rated".
- `RatingService::attachDriverRating(Trip)` / `attachDriverRatingToTrips(Collection)` — ONE grouped `SELECT ratee_id, COUNT(*), AVG(rating)` query populates `driver_rating_count` / `driver_rating_avg` on trip cards. Replaces the nested builder `withCount('driver.ratingsReceived')` aggregate (see §5 — unsupported for dot-notation in this framework).
- Web: `POST /ratings/{booking}` (Alpine 1–5 star picker component, note ≤500, submit disabled until a star is picked) wired into `bookings/index` for completed rides (passenger rates driver + driver rates each passenger). Admin `GET /admin/ratings` — driver scoreboard (avg desc, count desc) + recent ratings with stars/notes. Driver stars shown on `trips/board` cards and `trips/show`.

**B. Safety pack — Share Trip, SOS, emergency contact**
- `GET /trips/{trip}/share` — guest-safe public card (corridor, departure, seats, fare, women-only badge, driver + verification badge); 404 unless scheduled/active; no live location streamed. "Share this ride" copy-link button on `trips/show`.
- `POST /trips/{trip}/sos` — participants only (`Trip::isParticipant`), writes `ActivityLog::log('sos', …)` with corridor, route, lat/lng, `reported_at`; Control Tower dashboard gains a **Safety alerts (SOS)** panel of recent SOS rows (name, route, coords, age).
- Emergency contact (name + phone) on a new `GET/POST /profile` page (Profile & safety, linked from the profile menu). Never shared with other riders.

**C. Women-only preference (never a hard sort)**
- Schema: `users.gender` (nullable), `users.prefers_women_only` (bool, default false), `trips.women_only` (bool, default false) + composite index `[women_only, status, departure_time]`.
- `TripService::publish` writes `women_only`; `BookingService::book` gates it (`women_only && gender !== 'female'` → ValidationException "This is a women-only ride."); board chip is an opt-in filter, **defaulted on from the rider's profile preference** — explicitly not a hard sort. `trips/create` gains a Women-only toggle; `trips/show` shows the badge + a women-only block panel for non-female riders with a link to Profile & safety.

**D. Offline trip board (PWA)**
- `/offline` page (guest-safe, cached-board copy + retry link) added to the service worker SHELL; navigation requests that fail fall back to `/offline`; new `SKIP_WAITING` message handler. Read-only caching only — never caches POSTs, so offline can never race the `FOR UPDATE` seat locks.

**E. Design tokens file**
- Extracted the entire Tailwind v4 `@theme` token block (fonts + forest/gold/ink/paper palettes) and the base layer out of `resources/css/app.css` into `resources/css/design-system.css`; `app.css` now imports it. Brand tokens live in exactly one file.

**F. Landing investor KPI strip**
- `HomeController` now passes live KPIs (scheduled trips, rides completed today, verified workers, free volunteer rides) and `landing.blade.php` renders a 4-cell strip between hero and value props — funder-ready "look, it's alive" numbers.

**Tests (19 new — 309 total, 950 assertions):** `RatingsSafetyTest` — ratings gate/once-per-booking/mutual/stranger/not-completed/validation/scoreboard · women-only block + female allow + board pref default · public share + completed-404 · SOS audit log + non-participant 403 · profile safety/preference save · offline page · landing KPIs · service-worker offline fallback.

### 4.19 (placeholder — next sprint)

---

## 5. Issues Resolved

### Feature tests returning 404 on `/`
- **Symptom:** `php artisan test` failed — `test_the_application_returns_a_successful_response` got 404.
- **Root cause:** `APP_URL` contained a path (`/dev-angle/Starter-folder/workride/public`). Laravel feature tests resolve request URIs against `APP_URL`, so `get('/')` became the path `dev-angle/Starter-folder/workride/public`, which matched no route.
- **Fix:** Added `<env name="APP_URL" value="http://localhost"/>` in `phpunit.xml`.
- **Status:** ✅ Resolved — all tests pass.

### `php artisan reverb:install` interactive crash
- **Symptom:** `TypeError` in `Laravel\Prompts\select()` when run with `--no-interaction` inline with `telescope:install`.
- **Fix:** Ran `php artisan reverb:install --no-interaction` on its own. It also triggered the `install:broadcasting` config publish (broadcasting config + channels route).
- **Status:** ✅ Resolved.

### Duplicate index on `verifications.status`
- **Symptom:** `migrate:fresh` failed — `Duplicate key name 'verifications_status_index'` (SQLSTATE 1061).
- **Root cause:** The migration declared `$table->string('status', 20)->default('pending')->index()` and then `$table->index('status')` again at the bottom — MySQL refuses duplicate index names.
- **Fix:** Removed the redundant `$table->index('status')`.
- **Status:** ✅ Resolved.

### `hashDocument()` could not read uploaded files
- **Symptom:** Workplace-ID upload returned 500 — `file_get_contents(verifications/….jpg): Failed to open stream`.
- **Root cause:** `VerificationService::hashDocument()` passed a storage-relative path to `file_get_contents()`, which resolves relative to the process CWD, not the `public` disk.
- **Fix:** Read via `Storage::disk('public')->get($path)`.
- **Status:** ✅ Resolved — verified in tests with `Storage::fake('public')`.

### Enum cast returned `null` for freshly created users
- **Symptom:** Dashboard 500 — `Attempt to read property "value" on null` in `layouts/app.blade.php` (`verification_level->value`).
- **Root cause:** `UserFactory` did not set `role`/`verification_level`, so the in-memory model attribute was `null` before any DB round-trip applied the column default.
- **Fix:** `UserFactory` now seeds `role = passenger` and `verification_level = 0` (plus `is_banned = false`).
- **Status:** ✅ Resolved.

### Privilege escalation via self-selected `admin` role
- **Symptom:** Registration validation accepted `role=admin` (any `UserRole` case), so anyone could create an admin account.
- **Fix:** Added `UserRole::assignableCases()` (excludes `admin`/`workplace_admin`) and used it in both web + API registration validation.
- **Status:** ✅ Resolved — regression-tested.

### API logout crashed with `TransientToken`
- **Symptom:** `POST /api/v1/auth/logout` → `Call to undefined method TransientToken::delete()`.
- **Root cause:** When the sanctum guard falls back to an authenticated web session, `currentAccessToken()` returns a `TransientToken` (no-op token) rather than a `PersonalAccessToken`.
- **Fix:** Only delete when the token is a real `PersonalAccessToken`; `TransientToken` is left alone.
- **Status:** ✅ Resolved.

### Carbon "Unknown unit 'ToMinute'" in TripFactory
- **Symptom:** 27 tests errored — `Unknown unit 'ToMinute'.`
- **Root cause:** `TripFactory` called `now()->floorToMinute()`. Carbon's `__call` magic parses `floorToMinute` as `floor('ToMinute')` (an invalid unit) instead of a real method.
- **Fix:** Replaced with the built-in `floorMinute()`.
- **Status:** ✅ Resolved — full suite green.

### Web-route validation errors not rendering as JSON despite `postJson`
- **Symptom:** `test_message_validation_requires_text` got a 302 redirect instead of 422; the failure surfaced as `Call to a member function all() on array` inside Laravel's test assertion rendering.
- **Root cause:** `bootstrap/app.php` set `shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'))`, which **overrides** (not augments) the framework default `$request->expectsJson()`. Web routes never returned JSON validation errors even when the client sent `Accept: application/json`.
- **Fix:** `shouldRenderJsonWhen` now returns `$request->is('api/*') || $request->expectsJson()`.
- **Status:** ✅ Resolved — 422 JSON returned for JSON-expecting web requests.

### Fresh wallets failed the optimistic-lock update (`version` null in memory)
- **Symptom:** First `creditSubsidy`/`creditCash` on a user with no prior wallet threw `Wallet changed concurrently. Please retry.` — hit by CSV bulk credit and Paystack webhook crediting.
- **Root cause:** `Wallet::create()` leaves `version` unset in memory; the DB default `1` is only applied on insert. The optimistic-lock `WHERE version = ?` then compared against `null`, matched nothing, and reported a false "concurrent change".
- **Fix:** Added model-level `$attributes` defaults on `Wallet` (`cash_balance = 0`, `subsidy_credits = 0`, `cash_collected_log = 0`, `version = 1`) mirroring the DB defaults. Also hardens the pre-existing booking-hold flow for passengers with no wallet.
- **Status:** ✅ Resolved — covered by `SubsidyTest` bulk-credit + `WalletFundingTest` webhook paths.

### `RoadEvent.is_confirmed` was null for freshly-created models
- **Symptom:** `recordEvent()` returned `is_confirmed` = `null` (not `false`) and the confirmed-pothole refresh branch never fired; `RoadSensorTest` failed asserting `event.is_confirmed` is `false`.
- **Root cause:** Same latent bug as the Wallet one — `RoadEvent::create()` leaves `is_confirmed` unset in memory; the DB default `false` is only applied on insert, so the in-memory attribute stayed `null` until a DB round-trip.
- **Fix:** Added model-level `$attributes` defaults on `RoadEvent` (`severity = 1`, `is_confirmed = false`) mirroring the DB defaults.
- **Status:** ✅ Resolved — covered by `RoadSensorTest` + `RoadIntelligenceServiceTest`.

### Clustering ran but the confirmed event still read `false` after `recordEvent()`
- **Symptom:** `test_record_event_confirms_and_refreshes_segment` failed — the DB row was confirmed but the returned model's `is_confirmed` was still `false`, so `refreshSegment()` never ran.
- **Root cause:** `confirmClusters()` updates the DB via `update(['is_confirmed' => true])`; the in-memory `$event` returned by `recordEvent()` was never refreshed.
- **Fix:** Added `$event->refresh()` in `recordEvent()` after `confirmClusters()`.
- **Status:** ✅ Resolved — covered by `test_record_event_confirms_and_refreshes_segment`.

### Public `/road/map` 500'd for guests (`Attempt to read property "id" on null`)
- **Symptom:** `test_public_road_map_page_renders` got a 500 — `auth()->user()->id` on line 7 of the compiled `layouts/app`.
- **Root cause:** `layouts/app.blade.php` reads `auth()->user()` unconditionally (nav + header), but `/road/map` is a public route.
- **Fix:** New guest-safe `layouts/public.blade.php` (optional user block, "Sign in"/"Dashboard" buttons); `road/map.blade.php` now extends it.
- **Status:** ✅ Resolved.

### FERMA CSV export header assertions
- **Symptom:** `RoadAdminTest` failed on the CSV export — Laravel appends `; charset=utf-8` to `Content-Type` and a filename to `Content-Disposition`; `assertHeaderContaining` doesn't exist (it's `assertHeaderContains`).
- **Fix:** Assert `Content-Type` exactly (`text/csv; charset=utf-8`) and `Content-Disposition` via `assertHeaderContains`; read body with `getContent()` (controller returns a plain response, not streamed).
- **Status:** ✅ Resolved.

### `DemoUserSeeder` passed nested arrays straight into `User::updateOrCreate`
- **Symptom:** `php artisan db:seed` failed on the demo users — `SQLSTATE[42S22] Unknown column 'wallet' in 'field list'`.
- **Root cause:** Each demo-user array carried `wallet`/`vehicle`/`impact` sub-arrays, which were merged directly into the users insert as non-existent columns.
- **Fix:** Seeder now extracts the three nested keys (`unset`) before `User::updateOrCreate`, then applies wallet/vehicle/impact via their own `updateOrCreate` calls keyed on `user_id`/`plate_number`.
- **Status:** ✅ Resolved — `php artisan db:seed` creates all 3 demo accounts; covered by `DemoUserSeederTest`.

### PWA manifest/test contract mismatches
- **Symptom:** `PwaControllerTest` failed — manifest is served as `application/manifest+json` (not `application/json`) with absolute `start_url`/`scope`/icon URLs; the service worker returns `application/javascript` (not `text/javascript`); PWA icons are static files with no route, so the feature-test client 404s them.
- **Fix:** Updated assertions to the real contract; replaced the icon HTTP request with an on-disk `assertFileExists` + `filesize` check.
- **Status:** ✅ Resolved.

### `admin/rewards` 500 — `no such column: reward_claims.reward_campaign_id`
- **Symptom:** `RewardTest::test_admin_can_create_and_toggle_campaign` got a 500 on the `admin/rewards` index view (`RewardClaim::with('campaign')` failed).
- **Root cause:** `RewardClaim::campaign()` and `RewardCampaign::claims()` used Eloquent's default FK inference — `reward_campaign_id` — while the actual column is `campaign_id`.
- **Fix:** Both relations now pass the FK explicitly (`belongsTo(RewardCampaign::class, 'campaign_id')` / `hasMany(RewardClaim::class, 'campaign_id')`).
- **Status:** ✅ Resolved — regression-tested.

### Creating a reward campaign dropped `reward_type` (NOT NULL)
- **Symptom:** POST `/admin/rewards` → `Integrity constraint violation: reward_campaigns.reward_type`.
- **Root cause:** `Admin\RewardController::store()` validated `type`/`value` but only mapped `value` → `reward_value`; `reward_type` (a NOT NULL column) was never set.
- **Fix:** `store()` now maps `reward_type` from `$data['type']` too.
- **Status:** ✅ Resolved — covered by the admin create/toggle test.

### `EmployerLedgerService::refund()` threw on confirmed-then-cancelled bookings
- **Symptom:** Cancelling a booking that was still `confirmed` (never boarded, so no COVER was written) raised an exception inside `refund()`.
- **Root cause:** `refund()` refunded unconditionally, but an employer can only refund money the wallet actually moved — and money only moves at boarding (`cover()`).
- **Fix:** `refund()` now returns early unless an `EMP-{booking}-COVER` transaction exists.
- **Status:** ✅ Resolved — covered by `EmployerTest` cancel-refund cases.

### Nested builder aggregate `withCount('driver.ratingsReceived')` crashed the trip board
- **Symptom:** `/trips` 500 — `BadMethodCallException: Call to undefined method App\Models\Trip::driver.ratingsReceived()`.
- **Root cause:** This framework's `Builder::withAggregate()` resolves the relation via `getRelationWithoutConstraints($name)` which calls `getModel()->{$name}()` **without** splitting dot-notation, so nested `withCount`/`withAvg` on the query builder is unsupported (`Trip->driver.ratingsReceived()`).
- **Fix:** `RatingService::attachDriverRatingToTrips(Collection)` runs ONE grouped `SELECT ratee_id, COUNT(*), AVG(rating)` on `ride_ratings` for the trips' `driver_id`s and attaches `driver_rating_count` / `driver_rating_avg` as dynamic attributes. Both `TripMatchingService::upcoming()` and `TripBoardController::show()` now use it.
- **Status:** ✅ Resolved — covered by `RatingsSafetyTest` board + show flows.

### Duplicate rating on SQLite raised a raw PDO exception, not a friendly error
- **Symptom:** The second rating attempt in `RatingsSafetyTest` 500'd — `PDOException: SQLSTATE[23000] Integrity constraint violation: 19 UNIQUE constraint failed`.
- **Root cause:** The `QueryException` catch only accepted MySQL's driver code `23000`; SQLite reports `19`. The exception propagated as a 500 instead of "already rated".
- **Fix:** Added a pre-insert `exists()` check inside the transaction plus a tolerant catch for both `'23000'` and `'19'`.
- **Status:** ✅ Resolved.

### Blade HTML-encodes apostrophes in rendered copy
- **Symptom:** `assertSee("You're offline")` failed — the rendered page contains `You&#039;re offline`.
- **Root cause:** Blade `{{ }}` HTML-encodes `'` to `&#039;`, so literal-copy assertions must match the encoded output or use a plain-text fragment.
- **Fix:** Asserted the un-encoded sentence `Your connection dropped` instead.
- **Status:** ✅ Resolved.

---

## 6. How to Work On This Project

### Run the full dev environment (server + queue + logs + vite)
```bash
composer run dev
```
Runs concurrently: `php artisan serve`, `queue:listen`, `pail`, `npm run dev`.

### Individual commands
```bash
php artisan serve              # dev server
php artisan queue:listen       # queue worker
php artisan pail               # live log tailing
npm run dev                    # vite HMR
php artisan test               # run tests
php artisan pint               # format code
php artisan gtfs:generate      # regenerate the GTFS static feed zip
php artisan ide-helper:generate  # refresh IDE autocomplete
```

### Useful endpoints
| Path | Purpose |
|------|---------|
| `/` | Landing page (redirects to `/dashboard` when logged in) |
| `/dashboard` | Rider dashboard (wallet, verification, impact) |
| `/verify` | Level 1 workplace ID + Level 2 NIN submission |
| `/admin` | Ops Control Tower — dashboard, verifications, users, workplaces |
| `/admin/gtfs` | GTFS Publisher — feed status, download, regenerate |
| `/gtfs/gtfs.zip` | Public static GTFS feed (7-file zip) |
| `/gtfs/gtfs-rt/vehicle_positions.pb` | GTFS-realtime VehiclePositions feed (protobuf) |
| `/impact` | Personal impact (CO₂/fuel/trips) + leaderboards |
| `/impact/certificate/{co2|fuel}` | Printable QR-verifiable CO₂/Fuel certificate |
| `/impact/verify/{user}/{type}` | Public certificate verification (QR target) |
| `/manifest.json` | PWA Web App Manifest |
| `/sw.js` | PWA service worker |
| `/telescope` | Debug dashboard (requests, queries, jobs, mail) |
| `/api/v1/auth/*` | Sanctum API — register, login, me, logout |
| `/api/v1/verifications/*` | Sanctum API — submit workplace/NIN verification |

### Seeded admin
`admin@workride.ng` / `admin1234` (via `config/workride.php` → `WORKRIDE_ADMIN_EMAIL` / `WORKRIDE_ADMIN_PASSWORD`).

### Demo accounts (funding-pitch / demo — password `demo1234`)
| Email | Role | Notes |
|-------|------|-------|
| `driver@workride.ng` | Aisha Bello — L3 paid driver | Coaster ABJ-849-KJ, ₦12,450 wallet, 42-trip impact |
| `volunteer@workride.ng` | Chinedu Okafor — volunteer | Free-ride supply, 15-trip impact |
| `passenger@workride.ng` | Fatima Yusuf — L1 passenger | ₦3,200 cash + ₦15,000 subsidy credits |

---

## 7. Roadmap (from the guide — 8 sprints)

| Sprint | Scope | Status |
|--------|-------|--------|
| Sprint 1 (Wk 1-2) | Auth + Verification (NDPR compliant, Google sign-in) | ✅ Complete |
| Sprint 2 (Wk 3) | Trip + Booking atomic + Reverb chat | ✅ Complete |
| Sprint 3 (Wk 4) | Wallet dual balance + Paystack + subsidy bulk credit | ✅ Complete |
| Sprint 4 (Wk 5) | GTFS Publisher → submit to Google | ✅ Complete |
| Sprint 5 (Wk 6) | Road Sensor (`useRoadSensor.js`) + heatmap | ✅ Complete |
| Sprint 6 (Wk 7) | PWA award UI + impact certificates | ✅ Complete |
| Sprint 3.5 | Time-Bank (ride credits) + earned wallet + P2P transfers + payouts | ✅ Complete (feature-gated `FEATURE_TIME_BANK`) |
| Sprint 7 (Wk 8) | Business dashboard + receipts + exports | ✅ Complete |
| Sprint 8 | Employer mobility programs + rewards/green points + commodity commerce | ✅ Complete (feature-gated `FEATURE_EMPLOYER_PROGRAMS` / `FEATURE_REWARDS` / `FEATURE_COMMODITIES`) |
| Sprint 9 | Missions + global nav redesign (⌘K palette, mobile tab bar, brand mark) | ✅ Complete (feature-gated `FEATURE_MISSIONS`) |
| Sprint 3.6 | Tiered KYC — open liveness + NIMC lookup + driver anti-spoof | ✅ Complete (feature-gated `FEATURE_LIVENESS` / `USE_IDENTITYPASS` / `USE_SMILE`) |
| Investor Adoptions A–F | Mutual ratings + safety pack + women-only preference + offline board + design tokens + landing KPIs | ✅ Complete (v0.10.0) |

### Immediate next steps
1. Enable Redis (GEO + queue) per the guide's tech stack
2. Add `maatwebsite/excel` for FERMA/CSV exports when needed
3. Add the v3.0/v4.0 operations tables (demand surveys, forecasts, assets, maintenance) as a follow-up schema pass

---

## 7.1 Version History (Git Tags)

> Policy per guide §19: each sprint ends in **one milestone commit + one annotated tag**, and
> every feature/process implementation passes the DoD ritual before its own commit.
> Update this table on every phase-end commit. Full workflow: `WORKRIDE-APP-GUIDE.md` §19.

| Tag | Sprint | State | Tests (assertions) | Date |
|-----|--------|-------|--------------------|------|
| `v0.2.0` | Baseline — Foundation (Sprint 1 + 2) | Scaffold + auth/verification/control tower + trips/bookings/chat | 71 (222) | 2026-08-01 |
| `v0.3.0` | Sprint 3 — Wallet + Top-up + Subsidy | Paystack top-up + webhook + wallet page + subsidy bulk credit (CSV) + MDA dashboard | 90 (269) | 2026-08-01 |
| `v0.4.0` | Sprint 4 — GTFS Publisher | Static feed zip (7 files) + GTFS-RT (protobuf) + nightly job + on-publish regen + admin dashboard | 108 (339) | 2026-08-01 |
| `v0.5.0` | Sprint 5 — Road Sensor + Intelligence + Routing | useRoadSensor.js + POST /api/v1/road-events + IRI clustering + FERMA export + RoutingService cost caps + docker-compose | 134 (409) | 2026-08-01 |
| `v0.6.0` | Sprint 6 — PWA + Impact | Web App Manifest + service worker + icons + /impact analytics + QR-verifiable CO₂/Fuel certificates + CalculateImpactJob + demo users | 159 (476) | 2026-08-01 |
| `v0.6.5` | Sprint 3.5 — Time-Bank + Earned + P2P + Payouts | Ride credits (seats owed/repaid) + triple-balance wallet (subsidy→earned→cash hold priority) + driver earnings (fare − commission − union − insurance) + P2P transfers (1% cash fee, earned free) + Moniepoint-mocked payouts — gated on `FEATURE_TIME_BANK` | 184 (561) | 2026-08-01 |
| `v0.7.0` | Sprint 7 — Business Dashboard + Receipts + Exports | Control Tower "Business" page (gross revenue, MRR, driver earnings, commission/union/insurance, subsidy issued/spent, corridor + revenue-per-day charts) + 5 QR-verifiable financial receipts (booking, driver earnings, wallet top-up, subsidy MDA, monthly statement) with public verify + 3 CSV exports (transactions, settlements, subsidy) | 209 (638) | 2026-08-01 |
| `v0.8.0` | Sprint 8 — Employer Mobility + Rewards + Commodity Commerce | Employer programs (full/one-way/percent/capped coverage, org-funded commutes, employer wallet + COVER ledger) + reward campaigns (cash/earned/subsidy/green-points, period dedupe, budget caps) + Green Points economy + commodity market & shop (positions, buy/sell, QR orders; subsidy never spendable) — gated on `FEATURE_EMPLOYER_PROGRAMS` / `FEATURE_REWARDS` / `FEATURE_COMMODITIES` | 255 (765) | 2026-08-01 |
| `v0.9.0` | Sprint 9 — Missions + Global Nav Redesign | Sponsor-defined missions (auto-verified rewards via trip-completion + road-event observation, photo-proof review flow, rider hub + Control Tower) + global nav redesign (⌘K command palette, profile menu, mobile tab bar, matching-anim SVG brand mark) — missions gated on `FEATURE_MISSIONS` | 274 (819) | 2026-08-02 |
| `v0.9.1` | Sprint 3.6 — Tiered KYC | Open staff-ID liveness (auto-approve on pass, manual-review fallback) + verification attempts/rate limit + encrypted selfie retention purge + NIMC-licensed NIN lookup (idempotent, capped, cost-logged) + Smile anti-spoof driver webhook + Control Tower needs-review queue & KYC cost dashboard — gated on `FEATURE_LIVENESS` / `USE_IDENTITYPASS` / `USE_SMILE` | 290 (896) | 2026-08-02 |
| `v0.10.0` | Investor-Guide Adoptions A–F | Mutual ride ratings (once per booking, change-control audited) + driver scoreboard · safety pack (public Share Trip page, one-tap SOS → Control Tower panel, emergency contact profile) · women-only preference (opt-in board filter, booking gate, never a hard sort) · offline trip board (PWA SW read-only navigation fallback + `/offline`) · design tokens file (`design-system.css`) · landing investor KPI strip | 309 (950) | 2026-08-02 |

---

## 8. Key Conventions & Notes

- App logic lives in services (`app/Services`) — keep controllers thin
- Money = `decimal(15,2)`; never store raw NIN — only SHA256 hash + last 4
- Enums in `app/Enums` (guide defines 10: UserRole, VerificationLevel, TripStatus, BookingStatus, Corridor, PaymentMethod, TransactionType, RoadEventType, RoadCondition, VehicleType)
- Registration only allows `UserRole::assignableCases()` (passenger/driver/both/volunteer) — admin roles come from the Control Tower
- Dual-app: Blade+Tailwind+Alpine Rider PWA (public) + Filament-style Ops Control Tower
- Design system: Forest Green `#2E7D32`, Gold `#FBC02D`, Slate `#0F172A`, Paper `#F6F9F6`; Sora/Inter/JetBrains Mono; 8px grid
- Git: Conventional Commits (`feat|fix|test|refactor|chore|docs|perf(scope): subject`); never stage `.env`/secrets; tag each sprint (`v0.X.0`); update this log before every commit
- Git cadence: commit after **every feature/process implementation** that passes the DoD ritual (pint → test → build → docs → stage → commit), one milestone commit + tag (`v0.X.0`) at each sprint boundary, and **`git push origin main && git push --tags` after every sprint** — per guide §19
