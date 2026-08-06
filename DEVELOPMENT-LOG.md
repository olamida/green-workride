# WorkRide — Development Log & Setup Documentation

> Companion to `WORKRIDE-APP-GUIDE.md` (the product spec). This document tracks the
> actual development work completed so far on the Green WorkRide platform.
> Last updated: 2026-08-06

---

## 1. Project Overview

**Green WorkRide** — Community-Focused, Subsidy-Enabled Transit Intelligence Platform.

- **Vision:** 3 layers — Ride-share & staff bus aggregator, GTFS publisher (first for Abuja), road intelligence network.
- **Architecture:** Dual-app system — 1) Rider PWA, 2) Ops Control Tower.
- **Business:** Community Interest Company (CIC) hybrid — 60% Community Trust, 40% For-Profit Operating Co.
- **Tagline:** *"Built by amateurs, for the working class. From Abuja to the world."*

The authoritative product specification is `WORKRIDE-APP-GUIDE.md` in this folder.

---

## 2. Current Status (Phase: Foundation / Sprint 9 + Sprint 3.6 + Investor-Guide Adoptions A–F + Sprint 10 + UI Compact & Mobile Pass + Sprint 11 Complete + Fleet Driver App UI + Rich Demo Seeder Suite + Trip Board Planning + Docs Pass + Realtime Board + Trust Reconciliation Report + Connect Guide + Map-First Board + Accessibility Pass + Guide Motion & Branding + Live Corridor Chips)

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
| Feature modules | ✅ Sprint 10 complete — Tier-0 phone-verified onboarding (OTP, rate-limited, SHA-256-hashed codes, single-use) unlocking booking before KYC + benefits string (subsidy/ride-credit/free-volunteer/women-only/employer-coverage/publishing) gated behind Level 1+ · Employer enrollment Forms 1 & 2 (self-request → pending queue → approve grants Level 1 + phone-verified, rejected/review lifecycle, CSV roster auto-creates staff accounts with temporary password + `EmployerWelcome`) |
| Feature modules | ✅ UI Compact & Mobile Pass complete — tightened layout (h-14 header, `max-w-5xl` main, reduced vertical rhythm on dashboard/wallet/bookings/trips), tablet/phone-usable responsive rules, PWA install CTA (profile menu + mobile More sheet via `installApp`/`mobileNav` Alpine), nav dedup (Impact/Missions removed from profile menu — already primary nav), 3 new page-specific animated SVG cards (`trip-fill-anim`, `demand-map-anim`, `navigation-anim`) |
| Feature modules | ✅ Sprint 11 complete — Operations & Demand Research schema pass (guide v4.0): 17 enums, 7 migrations (21 tables + `trips.asset_id`), 21 models, Fleet/Stakeholder/Forecast/Demand services + `CalculateDriverScoresJob`, Control Tower demand calendar + fleet + stakeholder + driver-scoreboard pages, rider demand check-in page + API field kit (surveys/check-ins/probes), Ops demo seeder (feature-gated) |
| Feature modules | ✅ Fleet Driver App UI complete — driver-facing `/fleet` page (assigned assets, status pill, pre-trip inspection form with photos, fault reporting, maintenance preview) + `POST /api/v1/fleet/{asset}/telemetry` OBD2 intake + fleet gate banner on trip publish (feature-gated `FEATURE_FLEET`) |
| Feature modules | ✅ Rich Demo Seeder Suite complete — 13 seeders + shared trait building a 100-account, 554-booking, 102-road-event, 92-survey demo world with a re-runnable completion marker |
| Feature modules | ✅ Trip board planning pass complete — 48h board window (day-ahead trips visible + bookable), departure-window filters (Leaving soon / Later today / Tomorrow / Anytime), "How to book" guide, book-ahead/live badges, cleaner empty state |
| Feature modules | ✅ Animations silenced site-wide (config `WORKRIDE_ANIMATIONS=false`) — the animated SVG brand cards are gated out until the site-wide animation language is reviewed |
| Feature modules | ✅ Site search button fixed — header ⌘K button no longer depends on Alpine `$dispatch` outside an `x-data` scope (native event dispatch) |
| Feature modules | ✅ Docs pass complete — `WORKRIDE-DESIGN-REVIEWS.md` (critiques of the seeding-data prompt + plan-ahead/live-loading + Time-Bank + EV lease-to-own, with ADOPT/ADAPT/DEFER verdicts) · `WORKRIDE-USER-GUIDE.md` (role-based rider/driver/volunteer/MDA/ops guide) · `WORKRIDE-DEV-GUIDE.md` (world-class engineering standards + known-traps table) · `WORKRIDE-ROADMAP.md` (honest gap list of unimplemented spec items, priority-ranked with "done when" criteria) |
| Feature modules | ✅ Realtime board + demand-aware planning pass complete — Trip interest (idempotent `trip_interests` per trip+user, Pending→Matched on booking, revert on cancel) + live seat-counter channel (`TripSeatsUpdated` on the public `trips` channel, `board-live.js` seat/Full/book-link updates) + active-first "Leaving soon" sort + demand-aware empty state (`demandSnapshot` → "N people want this journey" + top destinations) + "How to book / Next departure" guide + interest panel on `trips/show` + Community Trust float ledger (`community_trust` table + `TrustService` credit/debit/balance, idempotent `TB-FLOAT-{bookingId}` on Time-Bank float creation + `TB-REPAY-{bookingId}-{seats}` on repayment) |
| Feature modules | ✅ Community Trust reconciliation report complete — Control Tower `/admin/trust` (net balance + per-fund credit/debit/balance breakdown, float issued/released/outstanding KPIs, from-scratch running-balance rebuild flagging any drifted `balance_after`, recent movements) + full-ledger CSV export — closes the P3.3 ledger reconciliation backlog |
| Feature modules | ✅ Connect guide pass complete — participant-only live connection guide (`/trips/{trip}/guide`): Leaflet map + live driver position on the private `trip.{id}` channel (live target → next boarding waypoint → `none`), privacy = no coords ever broadcast to non-participants, walking ETA/distance via `RoutingService` foot profile (OSRM `foot`/Google `walking`/Mapbox `walking`) with haversine × `route_factor` straight-line fallback, 50 m arrived radius, `guide_opened` activity-log entry, a11y live regions + `prefers-reduced-motion` |
| Feature modules | ✅ Map-first trip board complete — Leaflet/OSM map canvas above the trip list (live trips pinned at `current_lat/lng`, scheduled pinned at corridor anchors Kubwa/Nyanya/Lugbe/CBD), color legend (green live / gold free volunteer / slate scheduled), tooltips with route · departure · seats · fare, click-to-view cards, live seat-counter updates push into the map via `window.__tripsMap.updateTripSeats()` |
| Feature modules | ✅ Accessibility pass complete — visible `:focus-visible` outlines (forest, 2px offset), `prefers-reduced-motion` collapse, Leaflet attribution sizing + 44×44 min hit-area for map controls, aria-live distance/ETA/status regions on the connect guide, aria-labeled board map region |
| Tests | ✅ 428 feature tests passing (… + fleet driver app UI + rich demo seeder suite + trip board planning + animation gate + trip interest / realtime board / trust ledger + connect guide / board map / foot-profile routing + guide states / branded pins / live corridor chips / seat-count ticks)

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

### 4.19 Sprint 10 — Tier-0 Phone-Verified Onboarding + Employer Enrollment Forms 1 & 2 (COMPLETE)

**The instant-booking gate (Tier-0) — a new phone-only trust tier below KYC.**

**Schema (3 migrations):**
- `add_phone_verified_at_to_users_table` — `users.phone_verified_at` (nullable datetime) as the Tier-0 trust signal; the `VerificationLevel` ladder (0 unverified → 1 workplace → 2 NIN → 3 driver) is untouched.
- `create_phone_otps_table` — `phone_otps`: `user_id`, `token_hash` (SHA-256, **raw code never stored**), `purpose`, `expires_at`, `consumed_at`, `attempts`; indexed `[user_id, purpose]`.
- `add_joined_via_to_employer_members_table` — `employer_members.joined_via` ENUM `self|employer` (default `employer`).

**Models + enums:**
- `App\Models\PhoneOtp` — `isExpired()/isConsumed()/isUsable()/matches()/recordAttempt()/consume()` + datetime/int casts.
- `App\Enums\EmployerMemberStatus` gains `Pending` + `Rejected` (labels); new `App\Enums\EmployerJoinVia`.
- `App\Models\User` — new entry gates: `hasVerifiedPhone()`, `canBook()` = not banned && (phone-verified **or** Level 1+), `canBookBenefits()` = not banned && Level 1+. `phoneOtps()` HasMany, `phone_verified_at` fillable + datetime cast.
- `App\Models\EmployerMember` — `joined_via` cast + `isPending()`.

**Services:**
- `PhoneVerificationService` — `sendOtp()` (validates phone format, cooldown + daily send limits, hashes the token, invalidates earlier un-consumed codes so only the newest works, notifies via `SendPhoneOtp`), `verifyOtp()` (latest usable code, cap attempts then burn, wrong codes count, expiry handled, sets `phone_verified_at` + `phone_verified` change-control log).
- `BookingService::book()` — benefits gates: women-only and free-volunteer rides require `canBookBenefits()`; employer coverage is skipped for phone-only riders; `resolvePaymentMethod()` throws for `SubsidyCredit|RideCredit` when not benefits-eligible ("Pay with wallet or cash").
- `TripService::publish()` — requires `canBookBenefits()` (phone-only users cannot publish trips even free volunteer rides).
- `VehicleService` — shared `store()` (plate unique, type enum, seats 1–100) + `assertNotOwned()` for the self-service fleet page.
- `EmployerService` — `requestJoin()` (pending lifecycle: active returns existing, suspended blocked, rejected may re-request), `approveMember()` (pending→active + `grantWorkplaceVerification()` + audit), `rejectMember()`, `grantWorkplaceVerification()` (sets Level 1 — **never downgrades** — and marks the phone verified when one is on file), `enrollMany()` Form 2 rewrite: **unknown emails are now auto-created** (temporary `Str::password(12)`, `EmployerWelcome` notification, Level 1 + phone-verified), header row auto-detected (email/name/phone/employee id).

**Controllers + routes (web):**
- `VerificationController` — `phone()` / `sendPhoneOtp()` / `verifyPhone()`; routes `verification.phone`, `verification.phone.send`, `verification.phone.verify`.
- `EmployerRequestController` — `employers()` (open programs), `join()`, `vehicles()` / `storeVehicle()` / `destroyVehicle()`; routes `employers.self`, `employers.join`, `employer.vehicles.{store,destroy}`.
- Admin `EmployerController` — `members()` / `pendingMembers()` (cross-employer approval queue) / `approveMember()` / `rejectMember()` / `reviewMember()` (return to queue) / `vehicles()`; routes `admin.employers.members`, `admin.employers.members.pending`, `admin.employers.members.approve|reject|review`, `admin.employers.vehicles`.

**Notifications:** `SendPhoneOtp` (channels `database`+`log`; code expires via `workride.phone_verification.otp_ttl_minutes`), `EmployerWelcome` (channels `database`+`mail`, temporary password).

**Views:** `verification/phone.blade.php` (two-step send/verify, `@error` inline), Tier-0 CTA banner on `verification/index`, phone status card + link on `dashboard`, phone row + employer mobility card on `profile/edit`, `employers/join` (open programs + my memberships), `employers/vehicles` (self-service fleet), admin `employers/{members,members-pending,vehicles}` + updated `show` CSV copy, landing pitch, `badge` gains active/suspended styles, profile menu Employer link.

**Gates & config:** `workride.phone_verification.enabled` (**true** — OTP on by default; other insurer/portfolio switches stay false) with `otp_ttl_minutes`, `otp_max_attempts`, `send_cooldown_seconds`, `send_daily_limit`; `workride.employer_programs.enabled` (**true**). `config/services.php` gains `termii` + `twilio` blocks (pluggable SMS provider slots); `.env.example` documents `FEATURE_PHONE_VERIFICATION`, `WORKRIDE_PHONE_*`, `WORKRIDE_SMS_*`, `TERMII_*`, `TWILIO_*`.

**Bugs found & fixed during hardening:**
- `EmployerService::parseRow()` called `extractEmail($cells[0])` (a string) against an `array` parameter → `TypeError` on every CSV enroll. Now `extractEmail($cells)` only.
- `EmployerTest::test_csv_enrollment_*` expected unknown emails to be **skipped** — the Form 2 rewrite auto-creates them, so the test now asserts the created user (Level 1, member active, `EmployerWelcome` sent).

**Tests (27 new — 336 total, 1057 assertions):**
- `PhoneVerificationTest` (15) — auth redirect, page render, send updates phone + stores hash only, no-phone error, earlier codes invalidated, verify marks verified + audits, wrong-code attempts then burn, expiry, send cooldown, daily limit, phone-verified wallet booking, subsidy blocked for phone-only, volunteer blocked, publish blocked (403).
- `EmployerEnrollmentTest` (12) — auth redirect, self-request pending + joined_via self, inactive blocked, rejected re-request, suspended blocked, approve grants Level 1 + phone-verified + audit, never downgrades NIN, non-pending approve blocked, reject + review lifecycle, admin members + pending queue render, header-labeled CSV auto-creates staff account, vehicle register/delete + foreign-vehicle 403.

### 4.20 UI Compact & Mobile Pass — Tightened Layout + PWA Install + Nav Dedup + Page-Specific Animations (COMPLETE)

Adopted from the UI polish request: the app felt airy and wasn't comfortable on phones/tablets, the profile menu repeated primary nav, and the brand needed more page-specific animated SVG cards beyond `matching-anim`. No schema changes; pure Blade/Tailwind/CSS/JS polish.

**Compact layout (`resources/views/layouts/app.blade.php`):**
- Header `h-16` → `h-14`, logo mark 8→7, main container `max-w-6xl` → `max-w-5xl`, main padding `py-8` → `py-6` + `px-4 sm:px-6`, body bottom pad `pb-20` → `pb-16` (mobile tab bar clearance).
- Wallet pill + ⌘K search now appear from `sm:` up (were `md:`); desktop primary nav stays `lg:` (so tablet 640–1024 uses the bottom tab bar — never both).
- Page rhythm tightened: `mb-8`→`mb-6`, `mt-8`→`mt-6`, `gap-6`→`gap-5`, `p-6`→`p-5` cards, `space-y-8`→`space-y-6` on `dashboard`, `trips/board`, `wallet/index`, `bookings/index`, `trips/show`.

**PWA install CTA (installable on phone/laptop):**
- iOS/metas: `mobile-web-app-capable`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style=default`, `apple-mobile-web-app-title=WorkRide` added to the head.
- New `installApp` + `mobileNav` Alpine data components (`resources/js/app.js`) — track `canInstall` from the existing `beforeinstallprompt` → `wr-install-ready` dispatch, call `prompt()`, and hide after `appinstalled`/`userChoice`.
- "Install app" entries wired into the profile dropdown (`resources/views/components/profile-menu.blade.php`) and the mobile More sheet (`resources/views/components/mobile-nav.blade.php`, which now uses `x-data="mobileNav"`). Gated by `x-show="canInstall" x-cloak` (new `[x-cloak]{display:none}` rule) so it only appears when the browser offers an install prompt.
- New `download` + `smartphone` icons in `icon.blade.php`.

**Nav dedup:** profile menu's "Your workspace" grid dropped the Impact + Missions links — they were already primary top-nav destinations, so the dropdown now only holds overflow (Dashboard, Wallet, Verify, Employer, Commodities, Shop, Road map, Profile & safety) per the guide's "everything else lives in ⌘K / profile menu / mobile More" rule.

**Three new page-specific animated SVG brand cards (following the `matching-anim` pattern):**
- `trip-fill-anim.blade.php` (dark ink-950) — a corridor stop with seat-fill dots that light up per car (`wr-seat-fill`, staggered delays) and a car that drives off along the route (`wr-car-drive` via `offset-path` + `wr-car-bob`). Wired into `trips/board` as a banner above the list ("Seats filling on this corridor — board before it departs").
- `demand-map-anim.blade.php` (light map pane, forest→gold gradient) — a `wr-map-pan` road map pans gently behind static demand pins (Kubwa/Berger/Banex/CBD) with pulsing `wr-ring` hotspots, a gold "12 people · almost filled" chip, and clustered car icons. Wired into `dashboard` "Your corridor is live" replacing the old `matching-anim` compact.
- `navigation-anim.blade.php` (dark, Google-directions style) — a route base lane, gold `wr-route-draw` path, `wr-dash-flow` overlay, moving gold car, origin/destination pins (Pickup/Work), and a live ETA + distance chip. Wired into `trips/show` sidebar for participants on scheduled/active trips.

**CSS (`resources/css/app.css`):** new keyframes `wr-seat-fill`, `wr-car-drive`, `wr-map-pan`, `wr-ring`, `wr-route-draw`, `wr-car-bob` + their classes; `[x-cloak]` helper for Alpine-gated UI.

**Tests:** no behavioral change — existing suite still green (336 tests, 1057 assertions) after `npm run build`. `pint` clean.

### 4.21 Sprint 11 — Operations & Demand Research Schema (v4.0) + Control Tower + Rider Demand Check-in (COMPLETE)

The guide v3.0/v4.0 operations pass: fleet lifecycle, stakeholder remittance, demand forecasting, and the BRT pre-design demand-research field kit (manual junction counts, probe dwell points, workplace OD surveys, rider check-ins, OD matrix) — "with ₦50k interns + phones vs $100k consultants."

**Enums (17 new, `app/Enums`):** `AssetStatus`, `AssetAcquisitionType`, `AssetType`, `FaultStatus`, `InspectionStatus`-style maintenance set — `MaintenanceStatus` (Scheduled/Due/InProgress/Done), `MaintenanceType` (Preventive5000km/MonthlyInspection), `RemittanceStatus`, `UnionCategory`-style — `StakeholderType`, `ForecastEventType` (Govt/Church/Mosque/Festive/Weather/FuelScarcity), `DemandDayType`, `DemandRequestStatus`, `OdSurveyMode`, `DutyRosterStatus`, `DriverScorePeriod`, `GtfsValidationStatus`, `PermitStatus`. (17 enum files total incl. `DutyRole`.)

**Schema (7 migrations, 21 new tables + `trips.asset_id`):**
- `2026_08_02_130000_create_demand_research_tables` — `junctions` (known waiting points Berger/Banex/Kubwa/Nyanya/Lugbe), `demand_surveys` (manual junction counts, `[junction_id, day_type, hour]` index), `probe_demand_points` (slow-car dwell aggregation, `[lat, lng]` + `last_seen_at` indexes), `od_surveys` (workplace OD survey, `[workplace_id, home_area]` index), `demand_requests` (rider check-ins, `[status, requested_at]` index), `od_matrix` (origin→destination snapshot, `[origin_area, destination_area]` + `[period_start, period_end]` indexes).
- `2026_08_02_130001_create_fleet_tables` — `assets` (`[status, corridor]` index), `maintenance_schedules` (`due_date` NOT NULL), `inspections` (`[asset_id, date]` index), `faults` (`[asset_id, status]` index), `telemetry` (`[asset_id, recorded_at]` index).
- `2026_08_02_130002_create_stakeholder_tables` — `unions`, `stakeholder_remittances` (unique `reference`).
- `2026_08_02_130003_create_forecast_tables` — `forecasts` (event calendar with `expected_demand_multiplier`).
- `2026_08_02_130004_create_ops_roster_tables` — `duty_rosters`, `schedules`, `car_pool`, `car_pool_availability`, `driver_scores` (unique `[user_id, period_start]`), `fuel_advances`, `subsidy_reports`.
- `2026_08_02_130005_create_gtfs_validation_tables` — `permits`, `gtfs_validations`.
- `2026_08_02_130006_add_asset_id_to_trips_table` — nullable FK `trips.asset_id` → `assets`.

**Models (21 new):** `Junction`, `DemandSurvey`, `ProbeDemandPoint`, `OdSurvey`, `DemandRequest`, `OdMatrix` (table `od_matrix` — Eloquent's default pluralization `od_matrices` was wrong), `Asset`, `MaintenanceSchedule`, `Inspection`, `Fault`, `Telemetry` (table `telemetry`), `Union`, `StakeholderRemittance`, `Forecast`, `DutyRoster`, `Schedule`, `CarPool`, `CarPoolAvailability`, `DriverScore`, `FuelAdvance`, `SubsidyReport`, `GtfsValidation`, `Permit`. `Trip` gains `asset()`; `User` gains `assets()`.

**Services:**
- `FleetService` — trip-publish gate: `assertPublishable()` (asset-light: no asset → no gate; explicit `asset_id` must belong to the driver; the *latest* inspection today decides — a failed inspection blocks until a later passing one clears it); `recordInspection()` (failed → auto-opens a fault ticket), `recordFault()`/`resolveFault()`, `scheduleMaintenance()` (preventive 5,000 km, monthly inspection; `due_date` always set), `recordTelemetry()` (mileage update + auto-queues the next preventive).
- `StakeholderService` — `recordForTrip()` idempotent per `REM-{bookingId}` reference (volunteer rides remit nothing; only carried paid bookings count), `settleDue()` (pending → paid with `paid_at`), `unionFor()` (corridor match preferred over generic chapter).
- `ForecastService` — Phase-1 manual multiplier: `suggest()` (predicted = avg last-4-same-weekday boarded/completed bookings × multiplier, weekday counted in PHP so MySQL/SQLite share one SQL path), `upcoming()` (event calendar), `defaultMultiplier()` (Govt/Festive/FuelScarcity 1.6, Weather 1.4, Church 1.3, Mosque 0.7).
- `DemandService` — BRT pre-design field kit: `recordSurvey()`, `junctionCounts()` (per-junction totals + top destinations), `recordProbePoint()` (150 m haversine merge via portable bounding-box + PHP distance), `checkIn()` (FCT-geofenced), `generateOdMatrix()` (derives the destination from the respondent's workplace zone — `od_surveys` has no `destination_area` column), `pendingRequests()`.
- `CalculateDriverScoresJob` (`app/Jobs`) — weekly per-driver snapshot: rides completed (status=completed, driven by `updated_at` — `trips` has no `completed_at`), green points, average rating, pothole reports.

**Controllers + routes:**
- Web `Web\DemandController` — `/demand` rider check-in page (junction picker + GPS + destination + passengers + "my check-ins" list).
- API field kit `Api\V1\DemandController` — `POST /api/v1/demand/surveys`, `/demand/checkins`, `/demand/probes` (Sanctum, geofenced).
- Admin: `Admin\OpsController` (`/admin/ops/demand` — junctions table + pending check-ins + OD matrix), `Admin\FleetController` (`/admin/fleet`), `Admin\ForecastController` (`/admin/forecasts` demand calendar + event POST), `Admin\StakeholderController` (`/admin/stakeholders` remittance ledger), `Admin\ScoreboardController` (`/admin/driver-scores`), plus `/admin/faults` + `/admin/maintenance`. Sidebar links wired for all six.

**Views:** `admin/ops/demand`, `admin/fleet/index`, `admin/forecasts/index`, `admin/stakeholders/index`, `admin/scoreboard/index`, rider `demand/index` with `signal` icon added to the icon set; profile menu + admin sidebar links.

**Config:** `workride.demand.enabled` (**on by default** — `FEATURE_DEMAND`, env default true, the rider check-in is the marquee ops feature), `workride.fleet.enabled`, `workride.stakeholders.enabled`, `workride.forecasts.enabled` (all off by default), plus `forecasts.seats_per_vehicle`; mirrored in `.env.example`.

**Seeder:** `DemoOpsSeeder` (gated — no-ops when features disabled): a few junctions, one union, one asset + inspection, one forecast event. Wired into `DatabaseSeeder` last.

**Bugs fixed during hardening (SQLite test DB surfaced what MySQL hides):**
- `OdMatrix`/`Telemetry` models relied on Eloquent pluralization (`od_matrices`, `telemetries`) while the migrations create `od_matrix`/`telemetry` — added explicit `$table`.
- `ForecastService` used MySQL-only `DAYOFWEEK()` — now counts the same weekday in PHP over the 4-week window.
- `DemandService::generateOdMatrix()` selected a `destination_area` column that doesn't exist on `od_surveys` — destination now derived from the joined workplace `zone`.
- Probe-point radius used `SQRT/POW` raw SQL (no SQRT on SQLite) — replaced with a portable bounding-box query + PHP haversine.
- The OD-matrix return count compared `period_start` with `= 'Y-m-d'` but the `date` cast stores `Y-m-d H:i:s` on SQLite — now `whereDate()`.
- The failed-inspection gate blocked *any* failed inspection today forever — now the **latest** inspection decides.
- `resolveAsset()` filtered to Active-only, so a grounded single assigned asset never blocked publishing — now resolves the single assigned asset regardless of status and lets `isServiceable()` throw.
- `recordForTrip()` incremented the created-count even when `firstOrCreate` matched an existing reference — now gated on `wasRecentlyCreated`.

**Tests (32 new — 368 total, 1151 assertions):** `OpsSchemaTest` (tables exist, `trips.asset_id`, unique references, `defaultMultiplier` map), `FleetGateTest` (no-asset no-gate, active passes, grounded blocks, failed-then-passing inspection clears, fault ticket on failure, resolution, preventive due_km, telemetry mileage + preventive queue, trip publishes asset_id, foreign asset rejected), `StakeholderRemittanceTest` (pending record, volunteer none, idempotent, settle flips to paid, corridor-match union), `DemandForecastTest` (survey API auth + create, check-in inside FCT + outside 422, probe merge 150 m, OD matrix from surveys, forecast multiplier math 0.75×2.0=1.5, all 5 admin ops pages + non-admin 403, forecast event default multiplier, driver score job weekly snapshot).

### 4.22 Fleet Driver App UI — Driver-Facing Fleet Page + OBD2 Telemetry Intake (COMPLETE)

Rider-facing UI layer on top of the Sprint 11 fleet schema/service layer (guide §11 asset-light model: leased 18-seaters, pre-trip inspection before publish, OBD2 telemetry, preventive maintenance).

**Controllers:**
- `Web\DriverFleetController` (`/fleet`) — index (feature gate → off-notice panel; assigned assets via `User::assets()`, today's inspections keyed by asset, open faults by reporter, upcoming maintenance), `inspect()` (403 unless `assigned_driver_id` matches, photo uploads to `inspections/{assetId}` on the `public` disk → `FleetService::recordInspection`, failed inspection auto-opens a fault ticket), `storeFault()` (description + severity 1–5), `storePhoto()`.
- `Api\V1\FleetController` — `POST /api/v1/fleet/{asset}/telemetry` (422 unless `assigned_driver_id === user->id`; validates lat/lng/speed/fuel_level/engine_fault_code/harsh_braking/mileage; calls `FleetService::recordTelemetry`, which updates asset mileage and auto-queues the next preventive maintenance).

**Views:** `resources/views/fleet/index.blade.php` — feature-off notice, empty state ("No bus assigned to you yet"), asset card with status pill (`x-badge` gained a `neutral` style), pre-trip inspection form (date + photos + oil level + pass/fail), fault-report form with severity select, My open faults + Upcoming maintenance panels.

**Wiring:**
- `routes/web.php` — `fleet.` routes (`fleet.index`, `fleet.inspect`, `fleet.faults`) in the auth group; `routes/api.php` — telemetry endpoint in `auth:sanctum`.
- Profile menu gains "My fleet" behind `@if (config('workride.fleet.enabled'))`.
- `TripBoardController::create()` loads `$asset` (single assigned asset when fleet enabled) + `$todayInspection` and passes them to `trips/create`; the publish form gains a fleet gate banner (cleared / failed / not-inspected → links to `route('fleet.index')`), matching the `FleetService::assertPublishable` gate already enforced in `TripService::publish` (latest inspection today wins).

**Tests (12 new — 380 total, 1189 assertions):** `DriverFleetTest` (guest redirect, feature-off notice, empty state, sees assigned asset, passing inspection persists, failed inspection opens fault ticket, foreign-asset 403, fault report + validation, API telemetry mileage + preventive schedule, API foreign-asset 422, trip-create gate status banner).

### 4.23 Rich Demo Seeder Suite — 100-Account, Operations-Ready Demo World (COMPLETE)

The rich demo suite (`WORKRIDE-PROMPT-SEEDING-DATA.md`) turns a clean install into a fundable, operable demo in one `db:seed` — no manual data entry for pitches or Ops walkthroughs.

**Shared trait (`database/seeders/Concerns/InteractsWithDemoData.php`):**
- `demoSynced()` / `markSuiteSeeded()` — the suite is guarded by an `activity_logs` marker (`action = rich_suite_seeded`) written by the **last** seeder, so re-running `db:seed` never duplicates. ⚠️ Deliberately NOT keyed on the first demo user (`demo001@workride.ng`): that user exists after `RichUserSeeder` alone, so using it as the guard made every later seeder skip itself on a fresh run (caught during validation — MySQL had seeded only users + junctions).
- `demoPasswordHash()` — one bcrypt hash shared across 100 users (the `hashed` cast skips re-hash, ~1 bcrypt cost per suite).
- `demoPhone(int)`, `ninFor(email)` (deterministic SHA-256 hash + last 4, raw NIN never stored), `demoReference(prefix, i)` (`PREFIX-DEMO-00001`).

**13 seeders (all idempotent):**
- `JunctionSeeder` — 45 Abuja junctions (Kubwa/Nyanya/Lugbe corridors).
- `RichUserSeeder` — 100 accounts: 30 L3 drivers, 15 L3 both, 10 L1 volunteers, 40 L1–2 passengers, 5 workplace admins (FMF/FMW/FMOT/NASS/CBN); phone-verified, NIN-hashed for L2+, women-only preference on a few.
- `RichVerificationSeeder` — workplace_id/nin/driver approvals + verification_attempts per tier.
- `RichVehicleSeeder` — 40 vehicles (coasters/staff buses/danfos/sedans).
- `RichWalletSeeder` — 100 wallets + 200 top-up/subsidy/earned transactions.
- `RichTripSeeder` — 80 trips (40 completed / 10 active / 22 scheduled / 8 cancelled) with relational waypoints + JSON snapshot; Carbon `roundMinute()`/`floorMinute()` don't exist in this version → `startOfMinute()`.
- `RichBookingSeeder` — 554 bookings, seat-safe, no duplicate `(trip_id, passenger_id)`, wallet holds/captures/refunds + cash/subsidy/ride-credit/free methods.
- `RichRideCreditSeeder` — 30 Time-Bank credits (owed/repaid/overdue/waived) + overdue flags.
- `RichTransferSeeder` — 40 P2P transfers (1% cash fee / free earned) + 20 driver payouts, each with ledger transactions.
- `RichRoadSeeder` — 102 road events (72 raw + 30 in 6 confirmed 5-report clusters) + 20 IRI segments (World Bank RoadLab bands).
- `RichDemandSeeder` — 92 junction counts, 40 rider check-ins, 25 OD surveys, 30 probe dwell points, 11 OD-matrix rows.
- `RichGtfsSeeder` — regenerates `gtfs.zip` (171 stops, 3 routes, 32 trips) from the seeded trips.
- `RichChatImpactSeeder` — 120 chat messages + 70 impact stats; **last** seeder → writes the suite marker.

**Wiring:** all 13 appended to `DatabaseSeeder` (after `DemoOpsSeeder`), `DemoOpsSeeder::seedJunctions()` left intact — both use idempotent `updateOrCreate(['name'])` so the 4 legacy + 45 rich junctions coexist.

**Bugs found & fixed during validation:**
- Marker-as-first-user guard (above) → replaced with an `activity_logs` completion marker.
- `ChatMessage` collection `whereIn('status', ['boarded','completed'])` matched nothing because `Booking.status` is a `BookingStatus` enum (strict string vs enum) → now compares against the enum cases (same bug class as the old `verifyBooking` fix).
- My own test assertions: rich demo users = 100 (not 105), vehicles = 41 (40 rich + 1 legacy), probe points = 30 (not 40), and a bogus `distinct()->count('trip_id')` line removed.

**Tests (1 new — 381 total, 1220 assertions):** `RichSeederTest` (seeds the full `DatabaseSeeder` on SQLite; asserts user/vehicle/trip/booking counts, zero duplicate booking pairs, non-negative wallet balances, ride credits/P2P/payouts, confirmed road events + segments, demand counts, chat + impact + GTFS meta exist, every demo user has a wallet).

### 4.24 Trip Board Planning Pass + Animations Off + Site Search Fix (COMPLETE)

Root-cause fixes from the post-fleet review. The board looked empty except at peak, the animated SVG brand cards weren't showing a real map (and threw a stale-view `Undefined array key "x"` once), and the header search button relied on Alpine magic that may not resolve outside an `x-data` scope.

**Trip board planning (the "clicking a trip does nothing" fix):**
- Root cause: `TripMatchingService::upcoming()` only returned trips departing within `departure_window_minutes` (30) — at runtime 0 trips left in the next 30 min while 13+ day-ahead scheduled trips existed. The board wasn't broken; it was just empty except at peak, so there was nothing to click.
- Config: new `workride.board_window_minutes` (default **2880** = 48h) + `workride.board_window_presets` (`now` 30 / `later` 240 / `tomorrow` 1440 / `any` 2880). The live API `findMatches()` keeps its tight 30-minute window so near-term seats aren't pre-empted; only the web board widened.
- `TripBoardController::index()` now accepts `?window=now|later|tomorrow|any` and `?women_only=` (defaulting from the rider's profile preference, still never a hard sort).
- `trips/board.blade.php` rewritten: "How to book a seat" 3-step guide strip, corridor chips + Women-only filter, departure-window chips, trip cards with corridor/free/volunteer/women-only/**Live now**/**Book ahead** badges, departure time, seats, driver + rating, fixed fare, and a clear "View & book →" call-to-action; day-ahead empty state links back to "Anytime (48h)".

**Animations silenced site-wide (per instruction — review later):**
- New `workride.animations.enabled` config (default `false`, flip `WORKRIDE_ANIMATIONS=true` to re-enable). All four animated brand cards — `matching-anim`, `demand-map-anim`, `navigation-anim`, `trip-fill-anim` — early-return when disabled, so landing/dashboard/trips pages render clean content-first with no decorative SVG map.
- `.env.example` documents the flag.

**Site search (⌘K) button fix:**
- The header "Search… ⌘K" button used `@click="$dispatch('open-command')"` — Alpine's `$dispatch` magic is only guaranteed inside an `x-data` component scope, and the header sits outside any. Replaced with a native `onclick="window.dispatchEvent(new CustomEvent('open-command', { bubbles: true }))"` so the command palette always opens regardless of Alpine scoping.

**Homepage link on auth pages:** register page gains the same "← Back to homepage" link the login page already has.

**Bug found & fixed during hardening:**
- The "Book ahead" badge used `$trip->departure_time->diffInMinutes(now(), false) > 60` — Carbon's signed `diffInMinutes` returns a **negative** number when `$this` is in the future (verified: -926 for a day-ahead trip), so the badge never showed. Now `$trip->departure_time->gt(now()->addHour())` — unambiguous.

**Tests (3 new — 384 total, 1230 assertions):** `TripTest::test_board_shows_day_ahead_trips_by_default` (48h board shows + books a next-day trip with "Book ahead" badge), `TripTest::test_board_now_window_hides_day_ahead_trips` (Leaving soon filters them out with a clear empty state), `RatingsSafetyTest::test_landing_does_not_render_brand_animations_by_default` (gate keeps the SVG + label off the landing page).

### 4.25 Docs Pass — Design Reviews + User Guide + Dev Guide + Roadmap (COMPLETE)

Four companion docs so the spec stops being the only artifact. No schema/code changes — pure documentation that encodes the lessons of §5 and the honest gap list.

- **`WORKRIDE-DESIGN-REVIEWS.md`** — the "critique before backlog" contract. Reviews the seeding-data prompt (ADOPT: what shipped vs. asked, guard-rails added — idempotency marker, money invariants, no real PII, deterministic names), the plan-ahead/live-loading board idea (ADAPT: "the board shows ahead, the matcher books near" rule + missing predictive rail, live seat-counter, demand-aware empty state), Time-Bank ride-now-pay-later (ADOPT gated: the correction that the float must be real money and eligibility must guarantee repayability + backlog additions: trust float ledger, pre-due reminders, pay-it-forward statement), and FMWASD EV lease-to-own (DEFER hardware / ADOPT schema seams: `assets.propulsion`, `telemetry.battery_soc`, `lease_agreements`, `charging_stations`, gated `FEATURE_EV_LEASE`). Ends with a copy-paste template for new reviews.
- **`WORKRIDE-USER-GUIDE.md`** — role-based usage: Tier-0 phone onboarding + benefits ladder, passenger (find/book/ride/rate/receipts/safety/demand check-in), driver (Level 3, publish, pre-trip inspection via fleet, board/no-show/complete, withdrawal), volunteer (free rides + Green Points), workplace admin (subsidy bulk credit, employer Forms 1 & 2, coverage models), Control Tower (verifications/users/road/business/receipts/rewards/missions/GTFS + the Sprint 11 ops pages), feature-flag table, public surfaces, and a 5-step pitch-demo quickstart.
- **`WORKRIDE-DEV-GUIDE.md`** — the engineering contract: non-negotiables (hashed NIN, decimal money, atomic FOR UPDATE money moves, idempotency references, feature gates, board-vs-matcher split, change control), architecture, coding standards, the **known-traps table** (every §5 bug distilled into a row: enum-vs-string, null in-memory attributes, nested withCount, Storage::get, MySQL-only SQL, TransientToken, shouldRenderJsonWhen, Carbon signed diffs, $dispatch outside x-data, Eloquent pluralization, duplicate indexes, assignable roles, assertHeaderContains, Blade encoding, SQLite vs MySQL driver codes), and the Definition-of-Done ritual.
- **`WORKRIDE-ROADMAP.md`** — the honest gap list, priority-ranked with "Done when" criteria: P1 demo-critical cheap (seeder README, Google OAuth, live seat-counter, "Leaving soon" boost, demand-aware empty state), P2 production wiring (Paystack/Termii/IdentityPass/Smile/Moniepoint live, Redis, OSRM self-hosted, Google Transit submission), P3 guide features not yet built (USSD, FCM, trust float ledger, ride-credit reminders, employer CSV self-service, corridor-fare config UI, maatwebsite/excel, EV schema seams, forecast ML job, rider-facing driver scorecards, pay-it-forward statement, multi-tenant cities, demand bot, employer CO₂ report), P4 explicitly deferred 2028 ideas (AR/voice/haptics, insurance, union shares, FERMA MOU).

**Docs updated:** `DEVELOPMENT-LOG.md` status table (§2), roadmap (§7), version history (§7.1).

**Tests:** no code change — 384 total, 1230 assertions remain green; `pint` clean.

### 4.26 Realtime Board + Demand-Aware Planning + Community Trust Float Ledger (COMPLETE)

The board stops being static between requests, and the Time-Bank float becomes auditable. Closes roadmap P1.3, P1.4, P1.5 and the ledger half of P3.3.

**Schema (2 migrations):**
- `create_community_trust_table` — `reference` (unique), `direction` (credit/debit), `type`, `amount`, `meta` json, `created_at` — the Trust float ledger ("who owes the Trust a seat, what the Trust paid for").
- `create_trip_interests_table` — `trip_id` FK, `user_id` FK, `status` (default pending), `registered_at`, `notified_at`, `matched_at`; unique `[trip_id, user_id]`, index `[status, registered_at]`.

**Enums (3 new):** `TripInterestStatus` (Pending/Notified/Matched), `TrustLedgerDirection` (Credit/Debit), `TrustLedgerType` (FloatIssued/FloatRepaid/FloatRefunded/FloatWaived).

**Models (2):** `TripInterest` (`trip()`/`user()` + casts, idempotent per trip+user), `CommunityTrust` (table `community_trust`, reference-unique).

**Services:**
- `TripService::registerInterest(Trip, User)` — validates not-own-trip / not-completed-cancelled / not-departed; `updateOrCreate` on `[trip_id, user_id]` resetting to Pending with `registered_at = now()`.
- `BookingService` — after a successful booking, upgrade the rider's interest to **Matched** (`matched_at`); on cancel, revert to **Pending** (clears `matched_at`); both fire `TripSeatsUpdated` for the live board.
- `TripMatchingService::upcoming()` — reworked ordering: `ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, departure_time ASC`; each trip gets a dynamic `leaving_soon` flag (active, or departing within `workride.departure_window_minutes`).
- `DemandService::demandSnapshot()` — pending `demand_requests` in the last 24 h → `['people' => int, 'top_destinations' => array]` for the board's live signal.
- `TrustService` — idempotent `credit()`/`debit()` keyed on reference (no-op returns the existing row) + `balance(?type)`, gated on `workride.time_bank.enabled`.

**Event + realtime client:**
- `TripSeatsUpdated` (`app/Events`) — broadcasts `{trip_id, available_seats, total_seats}` on the public `trips` channel; static `forTrip(Trip)` factory; fired from `BookingService` on book and cancel.
- `board-live.js` — Alpine `boardLive` component: `.TripSeatsUpdated` listener updates `[data-seats]` text with a gold flash, toggles `[data-seats-full]` ("Full" pill), and disables `[data-book-link]` when seats hit 0; also hears `.TripPublished` and marks the new trip live. Registered in `app.js`.

**Wiring:**
- `TripBoardController::index()` passes `$demandSnapshot`, `$nextTrip` (first upcoming), plus `?window=`/`?women_only=` filters; `show()` passes `$myInterest` + `$interestCount`. New `POST /trips/{trip}/interest` (`trips.interest`).
- `trips/board.blade.php` — "How to book a seat" guide with **Next departure** (or demand-aware strip when empty), departure-window chips, trip cards with **Live now / Leaving soon / Book ahead** badges, demand-aware empty state ("N people want this journey" + top destinations + "Check in" / "I need a ride" links).
- `trips/show.blade.php` — interest panel (not full/not my trip): "Trip is full", "I want this journey" register button, or "You're on the list" pill with live interest count.
- `RideCreditService` — Time-Bank float creation credits the Trust (`TB-FLOAT-{bookingId}`) and repayment debits it (`TB-REPAY-{bookingId}-{seats}`, booking key fallback to credit id).

**Bugs fixed during hardening:**
- `board.blade.php` threw a Blade compile error (`unexpected token endif`) at runtime: a text-glued `right now@if (...)` — Blade's `\B@` directive regex requires non-word whitespace before `@`, so the inline `@if` stayed literal and its `@endif` orphaned. Moved `@if` onto its own line.
- The four board tests all failed on that compile error (no cards rendered); the interest `matched`-on-cancel assertion also needed interest registered *before* booking, and the seat-broadcast test needed a funded wallet before a wallet booking could dispatch the event.

**Tests (11 new — 395 total, 1254 assertions):** `TripInterestTest` — register interest, idempotent per trip+user, driver-own trip rejected, completed/departed rejected, booking upgrades to matched, cancel reverts to pending, demand-aware empty state, next-departure guide, active-first sort, leaving-soon/book-ahead badges, `TripSeatsUpdated` dispatch on wallet booking.

### 4.27 Community Trust Reconciliation Report + Trust Ledger Tests (COMPLETE)

Closes roadmap P3.3 — the ledger half shipped in §4.26, and now it can *prove* itself. The report rebuilds every running balance from the entries themselves, so a drift in `balance_after` (manual edit, double-write, missed entry) surfaces as a flagged mismatch instead of a silent black box.

**Controller (`Admin\TrustController`):**
- `index()` — loads the ledger ordered by `recorded_at, id`; aggregates per-fund credit/debit/balance (all five `TrustLedgerType` funds), totals the net Trust balance, tracks Time-Bank float issued/released, and runs a **from-scratch reconciliation pass**: for every entry it recomputes the running balance per type (in the same write order `TrustService` uses) and compares against the stored `balance_after` (0.005 tolerance). Any mismatch collects into `$mismatchReferences`.
- `export()` — full-ledger CSV (reference, type, direction, amount, balance_after, recorded_at, meta JSON) via `php://temp`, `text/csv` + attachment disposition.

**View (`admin/trust/index.blade.php`):** 4 KPI cards (Trust balance, Float issued, Float released, Float outstanding) · reconciliation banner ("Ledger balanced" vs "Reconciliation needs review" with the drifted references) · per-fund breakdown table (credits/debits/balance) · recent movements ledger (reverse-chron, credit green / debit ink with − sign) · empty state. "Community Trust" link added to the Control Tower sidebar.

**Routes:** `GET /admin/trust` (`admin.trust.index`) + `GET /admin/trust/export` (`admin.trust.export`) in the admin group.

**Tests (12 new — 407 total, 1298 assertions):** `TrustLedgerTest` — credit idempotent on reference (no duplicate row), debit idempotent, net + per-type `balance()`, running `balance_after` per write, admin report render with KPIs + balanced banner, per-fund breakdown numbers, drifted `balance_after` flagged for review, CSV download (headers + rows + meta JSON round-trip parsed via `str_getcsv`), guest/admin gate (403 non-admin on both routes), empty-ledger state.

### 4.28 Connect Guide + Map-First Trip Board + Accessibility Pass (COMPLETE)

Three slices from the post-trust-report audit: a participant-only **connect guide** (passenger walks/ETA to their ride, live via the private channel), a **map-first trip board**, and an **accessibility hardening pass**. No schema changes; pure services/controllers/views/JS/CSS. Feature-gated where useful (`FEATURE_GUIDE`, default true).

**Config (`config/workride.php`):** `guide` block — `enabled` = `FEATURE_GUIDE` (default true), `walking_speed_kmh` 5, `route_factor` 1.25 (straight-line × factor walking fallback), `arrived_radius_m` 50, `re_route_threshold_m` 150, `zoom_overview` 14, `zoom_follow` 16; `corridor_anchors` (Kubwa/Nyanya/Lugbe/CBD) so scheduled board trips pin at a real anchor point instead of `0,0`.

**`RoutingService` (`app/Services/RoutingService.php`)** — `route(from, to, profile = 'driving')` gains a walking profile threaded through all three providers (OSRM `/route/v1/foot/`, Google `mode=walking`, Mapbox `mapbox/walking/`); return arrays now include `provider`; cost-log payloads carry `profile`.

**`ConnectGuideService` (`app/Services/ConnectGuideService.php`)** — `targetFor(Trip)` (active + non-zero coords → `type:live` driver position; else next scheduled/active `TripWaypoint` by sequence → `type:waypoint`; else `type:none`), `walkingDistanceM()` (haversine × `route_factor`, `null` when no target), `walkingDurationS()`, `isArrived()` (≤ 50 m), `walkingRoute()` (OSRM foot via `RoutingService`; catches `Throwable` → `provider: straight_line` zero-cost fallback), `vehicleLabel()`.

**`GuideController` (`app/Http/Controllers/Web/GuideController.php`)** — `show()` participant-gated (`Trip::isParticipant`), status gate scheduled/active, writes a `guide_opened` change-control log (`ActivityLog::log`, `Trip::class`), passes config + `my_booking_id` for client-side cancellation filtering; `route()` validates the target exists (422 when `type:none`) and the point is inside the FCT geofence (422 otherwise). Routes `trips.guide.show` / `trips.guide.route` in the auth group. `trips/show` gains a "Connect guide" card (participants only) linking to the guide.

**Guide view (`resources/views/trips/guide.blade.php`)** — Leaflet map (`#connect-guide-map`), a11y live regions (`data-guide-distance` / `data-guide-eta` / `data-guide-status`, `aria-live="polite"`), "Meet the ride" + "How the guide works" side cards, privacy note ("your live position is never broadcast to other riders"), `@vite(['resources/js/connect-guide.js'])`.

**`connect-guide.js`** (new Vite entry) — initializes the map, pins the live/waypoint target, watches the passenger's `navigator.geolocation`, throttles route fetches (client straight-line catch fallback matching the service), listens on the private `trip.{id}` channel for `TripLocationUpdated` (re-route when moved > `re_route_threshold_m`), `TripCancelled` / `TripCompleted` (guide-over notice), and `BookingCancelled` (no-op unless it's *my* booking via `config.my_booking_id`); arrived → pan + status "You've arrived"; `prefersReducedMotion()` gates animated transitions. `window.initConnectGuide` exposed.

**Map-first trip board** — `trips-map.js` (new Vite entry): `window.initTripsMap(el, trips, { cbd })` pins live trips at `current_lat/lng` and scheduled trips at their corridor anchor, color = green live / gold free volunteer / slate scheduled, tooltips `route_name · departure · seats · fare`, click → trip page, returns `updateTripSeats(tripId, seats)` which the existing `board-live.js` `.TripSeatsUpdated` handler also calls (re-colors slate→gold and refreshes the tooltip when a free ride fills). `trips/board.blade.php` renders the map block above the list **only when trips exist**, with the `@vite` + init script moved inside the `@if` (so the string `trips-map` never renders on an empty board), an aria-labeled `role="region"`, and a legend line.

**Accessibility pass (`resources/css/app.css`)** — visible `:focus-visible` outline (forest `#2e7d32`, 2px offset) site-wide; `prefers-reduced-motion` collapses decorative animation to 0.01 ms; Leaflet attribution font-size 10px and 44×44 min hit-area for map controls (`.leaflet-bar a`).

**Bugs fixed during hardening:**
- `test_board_map_is_hidden_when_no_trips` failed `assertDontSee('trips-map')` because the map's `@vite(['resources/js/trips-map.js'])` + init `<script>` were rendered unconditionally at the bottom of `board.blade.php`, even though the `#trips-map` div was correctly gated — the string leaked into the empty-state DOM. Fixed by moving the `@vite` + `DOMContentLoaded` init inside the `@if ($mapTrips->isNotEmpty())` block (and deleting the orphaned bottom block).

**Tests (17 new — 424 total, 1337 assertions):** `ConnectGuideTest` (13) — guest redirect, non-participant 403, driver sees live target, passenger view + `guide_opened` activity-log row, next-waypoint fallback, completed/cancelled 404, `trips.guide.route` 200 for participants + walking payload, 403 non-participant, 422 outside FCT, 422 no-target, service walking math (factor + speed), straight-line fallback on provider failure, zero-coords never `type:live`. `RoutingServiceTest` — foot profile hits the `/route/v1/foot/` endpoint and reports `provider`. `TripTest` (3) — map present when trips exist, `Map view` + `initTripsMap` + legend render, map hidden (no `trips-map` string) when the board is empty.

### 4.29 Guide Motion & Branding + Live Corridor Chips + Seat-Count Tick (COMPLETE)

Adopted from the guide-motion review (AN01): the connect guide gets a purposeful, accessible, brand-aligned motion + state language, and the trip board's live corridor chips + seat counters now pulse/tick on live events. Forbidden by the review: 3D/pitch/robot-eye nav, Lottie/Rive, particles/confetti, motion while the user walks, and any change to money/verification/core matching — motion is feedback and orientation only, never decoration. No schema changes.

**Motion tokens (`resources/css/design-system.css`)** — `:root` motion scale (`--wr-motion-fast 150ms` / `normal 200ms` / `slow 320ms`), easing tokens (`--wr-ease-spring`, `--wr-ease-out`, `--wr-ease-in-out`), pulse tokens (`--wr-pulse-duration 2000ms`, `--wr-pulse-scale 1.04`, `--wr-pulse-opacity 0.7`), a `:root` reduced-motion override collapsing all motion tokens to `0ms`, `wr-transition-fast/normal/slow` utility classes, and reusable one-shot `wr-pulse` (transform+opacity, `transform-box: fill-box`), `wr-fade-in`, `wr-scale-in` + keyframes. The old opacity-only `wr-pulse` (consumed by `matching-anim`) is replaced by the token-based pulse.

**Guide styles (`resources/css/app.css`)** — `.wr-glass` frosted card (the app's existing `blur(20px)` + 12 % white elevation language, so the guide reads as one family with the rest of WorkRide), `.wr-seat-tick` (300 ms forest highlight + scale on a live seat-count), `.wr-number-tick` (150 ms gold flash on a changed distance/ETA number, no layout shift), branded map pins (`.wr-pin`/`.wr-pin-body`/`.wr-pin-badge` gold "B" / `.wr-pin-dot` "you" dot / `.wr-pin-soft` one-shot acknowledgement / `.wr-pin-move` 2-shot while-moving pulse — pins **no longer breathe infinitely**, so the map stays calm while walking), and `.wr-route-line` (forest polyline glow).

**Connect-guide states (`resources/js/connect-guide.js` + `resources/js/connect-guide-ui.js` + `resources/views/trips/guide.blade.php`):**
- The guide is now a three-state flow — **overview** (pin the vehicle, "Start guide" snap) → **active** (compact glass HUD while walking) → **arrived / missed** (terminal panels). The Alpine shell (`connectGuideUI`, registered in `app.js`) owns the state machine, the status copy and the HUD; the map module stays presentation-free and reports through callbacks (`onStatus` / `onDistance` / `onArrived` / `onMissed`).
- Branded `divIcon` pins: forest vehicle pin with a gold "B" badge, blue "You" pin with a white dot; solid 4 px forest polyline with the `wr-route-line` glow.
- Overview mode shows a **quiet straight-line estimate** (distance/ETA number tick) — no route fetch, no camera movement, no polylines until the passenger chooses to start, so the map never jumps before the user hands over.
- `startFollow()` snaps to the follow zoom (`fitBounds` / `setView` to `zoom_follow`), and only then draws the walking route; the vehicle pin does a one-shot pulse on `TripLocationUpdated`; the HUD distance/ETA re-trigger the 150 ms gold `wr-number-tick` on every update.
- Terminal states: **arrived** (`route.distance_m <= arrived_radius_m` → "You are here — wave to the driver" panel) and **missed** (`TripCancelled` / `TripCompleted` while not arrived / own `BookingCancelled` → slate panel with Find another ride / Open trip page / Call driver recovery). All Leaflet pan/zoom animations respect `prefers-reduced-motion`.
- `guide.blade.php` keeps the `#connect-guide-map` id and the `Boarding point → label` row (test contract: `Your ride · ABJ-777-KJ`, `Berger Junction`), adds `x-cloak` panels, `aria-live="polite"` status regions, 44 px touch targets, and a one-shot `wr-scale-in` entrance on the overview/arrived cards. A `type:none` target (no position, no waypoint) renders a dashed "No boarding point shared yet" notice in the map element and hides the Start button.

**Live corridor chips + seat-count tick (trip board):**
- `TripMatchingService::liveCorridors(): array<string,bool>` — corridors with a Scheduled/Active trip leaving within `workride.departure_window_minutes`; `TripBoardController::index()` passes `$corridorLive`; `trips/board.blade.php` chips render a `wr-pulse` live dot + `data-corridor-chip` (sr-only "live trips leaving soon") for live corridors and a quiet `opacity-40` dot otherwise.
- Seat counters now carry `data-seats data-corridor aria-live="polite"`, and `board-live.js` re-triggers the one-shot `wr-seat-tick` (via `remove → void offsetWidth → add`) on `.TripSeatsUpdated` instead of the old 1600 ms gold toggle — shorter, meaningful, token-based.
- Blade gotcha re-confirmed: inline `@php($x = ! empty($y))` with nested parens breaks the directive regex → switched to the block `@php … @endphp` form.

**Bugs fixed during hardening:**
- `assertSee('x-ref="hudDistance"')` failed because Blade/assertSee HTML-escapes the search string (`x-ref=&quot;hudDistance&quot;`) — the assertion now checks the unquoted `hudDistance` fragment.
- The old `.wr-pin-body` had an infinite breathing animation; while walking the vehicle pin should only pulse on movement — moved to one-shot `wr-pin-soft` / two-shot `wr-pin-move` classes toggled by JS.

**Tests (4 new — 428 total, 1361 assertions):** `ConnectGuideTest` (+2) — overview state renders the glass HUD, Start guide button, `connectGuideUI`, `data-config`/`data-target`, terminal panels, and the `route_url` JSON contract; a `type:none` target hides Start guide and shows `n/a`. `TripTest` (the 3 chip/seat tests landed in §4.28's follow-up) — live corridor chip pulses when a trip leaves soon, quiet corridor has no live dot, seat counter carries corridor + live region data.

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

### Blade compile error — `unexpected token "endif"` from a text-glued `@if`
- **Symptom:** every `/trips` render 500'd — `syntax error, unexpected token "endif", expecting end of file` at `trips/board.blade.php:35`. The failure page itself only rendered because the *compiled* PHP was broken, yet `Blade::compileString()` on the same source returned OK.
- **Root cause:** the view had `…want a ride right now@if (count($demandSnapshot['top_destinations']))`. Blade's directive regex is `\B@(?:…)` — a `@` glued directly after a word character (`now@`) is NOT a directive (it's treated as literal email-like text), so the `@if` stayed as literal markup while its paired `@endif` compiled to a real `<?php endif; ?>` — one orphaned `endif`.
- **Fix:** moved `@if` onto its own line (preceded by whitespace) so `\B@` matches and the pair compiles balanced.
- **Status:** ✅ Resolved — caught by the four board-rendering tests in `TripInterestTest`.

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
| Sprint 10 | Tier-0 phone-verified onboarding + employer enrollment Forms 1 & 2 | ✅ Complete (v0.11.0) |
| UI Compact & Mobile Pass | Compact layout + PWA install CTA + nav dedup + 3 page-specific animated cards | ✅ Complete (v0.12.0) |
| Sprint 11 | Operations & Demand Research schema pass (fleet, stakeholder, forecasts, demand field kit) + Control Tower pages | ✅ Complete (v0.13.0) |
| Rich Demo Seeder Suite | 13-seeder 100-account operations-ready demo world + seeder test | ✅ Complete (v0.14.0) |
| Trip Board Planning + Animations Off + Search Fix | 48h board window, window/women-only filters, "How to book" guide, book-ahead/live badges, animation gate, ⌘K fix | ✅ Complete (v0.15.0) |
| Docs Pass | `WORKRIDE-DESIGN-REVIEWS.md` + `WORKRIDE-USER-GUIDE.md` + `WORKRIDE-DEV-GUIDE.md` + `WORKRIDE-ROADMAP.md` | ✅ Complete (v0.16.0) |
| Realtime Board + Demand-Aware Planning | Trip interest (pending→matched/revert) + live seat-counter channel + active-first leaving-soon sort + demand-aware empty state + next-departure guide + Community Trust float ledger (`community_trust`, `TrustService`) | ✅ Complete (v0.17.0) |
| Community Trust Reconciliation Report | Control Tower `/admin/trust` (per-fund + net balance, float KPIs, from-scratch running-balance rebuild flagging drifted `balance_after`) + full-ledger CSV export + `TrustLedgerTest` | ✅ Complete (v0.18.0) |
| Connect Guide + Map-First Board + Accessibility | Participant-only live connect guide (`/trips/{trip}/guide`, private channel, walking ETA) + Leaflet map-first trip board (corridor anchors, live seats into map) + a11y hardening (focus-visible, reduced-motion, 44px map controls, aria-live) | ✅ Complete (v0.19.0) |
| Guide Motion & Branding + Live Corridor Chips | Connect guide three-state flow (overview → active HUD → arrived/missed) + branded pins + glass HUD + number ticks + motion tokens (reduced-motion-safe) + live corridor chip pulse + seat-count tick | ✅ Complete (v0.20.0) |

### Immediate next steps
1. Enable Redis (GEO + queue) per the guide's tech stack
2. Add `maatwebsite/excel` for FERMA/CSV exports when needed
3. ✅ DONE — Fleet Driver App UI wired (see §4.22)
4. ✅ DONE — Rich demo seeder suite (see §4.23); next: rider-facing driver scorecards
5. ✅ DONE — Trip board planning + animations gate + search fix (see §4.24); next: live seat-counter channel (see `WORKRIDE-ROADMAP.md` 1.3)
6. ✅ DONE — Docs pass (see §4.25); backlog lives in `WORKRIDE-ROADMAP.md`
7. ✅ DONE — Realtime board + demand-aware planning + trust float ledger (see §4.26); next: Trust reconciliation report + ledger tests
8. ✅ DONE — Trust reconciliation report + ledger tests (see §4.27); remaining P1 backlog: seeder README + Google OAuth (see `WORKRIDE-ROADMAP.md` 1.1, 1.2)
9. ✅ DONE — Connect guide + map-first board + accessibility pass (see §4.28); remaining P1 backlog: seeder README + Google OAuth (see `WORKRIDE-ROADMAP.md` 1.1, 1.2)
10. ✅ DONE — Guide motion & branding + live corridor chips + seat-count tick (see §4.29); remaining P1 backlog: seeder README + Google OAuth (see `WORKRIDE-ROADMAP.md` 1.1, 1.2)

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
| `v0.11.0` | Sprint 10 — Tier-0 Phone Onboarding + Employer Enrollment | Tier-0 phone-verified booking gate (OTP, SHA-256-hashed, rate-limited, single-use) unlocking `canBook()` before KYC, with the benefits string (subsidy / ride-credit / free-volunteer / women-only / employer-coverage / publishing) gated behind Level 1+ · Employer enrollment Forms 1 & 2 (self-request → pending → approve grants Level 1 + phone-verified, rejected/review lifecycle, header-detecting CSV roster that auto-creates staff accounts with temp password + `EmployerWelcome`) · shared `VehicleService` self-service fleet page · Control Tower pending-approval queue | 336 (1057) | 2026-08-02 |
| `v0.12.0` | UI Compact & Mobile Pass | Tightened layout (h-14 header, `max-w-5xl` main, reduced vertical rhythm) + PWA install CTA (profile menu + mobile More sheet via `installApp`/`mobileNav` Alpine, iOS metas, `x-cloak`) + nav dedup (Impact/Missions dropped from profile menu — already primary nav) + 3 new page-specific animated SVG cards (`trip-fill-anim` on trips board, `demand-map-anim` on dashboard corridor card, `navigation-anim` on trips/show for participants) with new keyframes (`wr-seat-fill`, `wr-car-drive`, `wr-map-pan`, `wr-ring`, `wr-route-draw`, `wr-car-bob`) | 336 (1057) | 2026-08-02 |
| `v0.13.0` | Sprint 11 — Operations & Demand Research (v4.0) | Guide v4.0 ops + BRT pre-design field kit: 17 enums, 7 migrations (21 tables + `trips.asset_id`), 21 models (fleet assets/maintenance/inspections/faults/telemetry, unions + stakeholder remittances, forecast demand calendar, demand surveys/probe points/OD surveys/check-ins/OD matrix, duty rosters/car pool/driver scores/fuel advances/permits/GTFS validations) + FleetService publish gate (latest-inspection-wins) + StakeholderService idempotent remittances + ForecastService weekday-multiplier suggestion + DemandService junction counts/150 m probe merge/FCT-geofenced check-ins/OD matrix + CalculateDriverScoresJob + Control Tower demand calendar/fleet/stakeholder/driver-scoreboard pages + rider `/demand` check-in + API field kit + gated `DemoOpsSeeder` — demand on by default (`FEATURE_DEMAND`) | 368 (1151) | 2026-08-02 |
| `v0.14.0` | Rich Demo Seeder Suite | 13 idempotent seeders + `InteractsWithDemoData` trait (activity-log completion marker) building a 100-account / 80-trip / 554-booking / 102-road-event / 92-survey operations-ready demo world + regenerated GTFS feed; `RichSeederTest` locks the whole chain on SQLite | 381 (1220) | 2026-08-02 |
| `v0.15.0` | Trip Board Planning + Animations Off + Search Fix | 48h board horizon (`board_window_minutes`, presets) + `?window=`/`?women_only=` filters + "How to book" strip + Book-ahead/Live-now badges (live matcher keeps its 30-min window) · animated brand cards gated behind `WORKRIDE_ANIMATIONS=false` · header ⌘K native-event dispatch · register homepage link | 384 (1230) | 2026-08-02 |
| `v0.16.0` | Docs Pass | `WORKRIDE-DESIGN-REVIEWS.md` (ADOPT/ADAPT/DEFER reviews: seeding-data prompt, plan-ahead/live-loading, Time-Bank trust float, EV lease-to-own) · `WORKRIDE-USER-GUIDE.md` (role-based usage) · `WORKRIDE-DEV-GUIDE.md` (engineering standards + known-traps table) · `WORKRIDE-ROADMAP.md` (priority-ranked gap list with "done when") | 384 (1230) | 2026-08-02 |
| `v0.17.0` | Realtime Board + Demand-Aware Planning | Trip interest (idempotent `trip_interests`, Pending→Matched on book / revert on cancel) + live seat-counter `TripSeatsUpdated` channel (`board-live.js`) + active-first "Leaving soon" sort + demand-aware empty state + "How to book / Next departure" guide + interest panel + Community Trust float ledger (`community_trust` + `TrustService`, idempotent `TB-FLOAT-{bookingId}` / `TB-REPAY-{bookingId}-{seats}`) | 395 (1254) | 2026-08-05 |
| `v0.18.0` | Community Trust Reconciliation Report | Control Tower `/admin/trust` — net + per-fund credit/debit/balance, float issued/released/outstanding KPIs, from-scratch running-balance rebuild flagging drifted `balance_after` (0.005 tolerance) + recent-movements ledger + full-ledger CSV export (meta JSON round-trip) + sidebar link + `TrustLedgerTest` (12) | 407 (1298) | 2026-08-05 |
| `v0.19.0` | Connect Guide + Map-First Board + Accessibility Pass | Participant-only live connect guide (`/trips/{trip}/guide` — Leaflet + private `trip.{id}` live driver/waypoint target, walking ETA via `RoutingService` foot profile + haversine × `route_factor` fallback, 50 m arrived, `guide_opened` audit log) · map-first trip board (live trips at coords, scheduled at corridor anchors, color legend, live seat-counter pushes into the map) · accessibility pass (`:focus-visible`, reduced-motion, 44×44 map controls, aria-live guide regions) — gated on `FEATURE_GUIDE` | 424 (1337) | 2026-08-06 |
| `v0.20.0` | Guide Motion & Branding + Live Corridor Chips + Seat-Count Tick | Connect guide three-state flow (overview quiet straight-line estimate + Start guide → active glass HUD with 150 ms gold number ticks → arrived / missed terminal panels) · branded map pins (forest vehicle + gold "B", blue "You" dot; one/two-shot pulses while moving, never a constant beat) · solid 4 px forest polyline glow · motion tokens + `wr-pulse`/`wr-fade-in`/`wr-scale-in` + `.wr-glass` (all collapse under `prefers-reduced-motion`) · `connectGuideUI` Alpine shell owns the state machine + HUD via callbacks · live corridor chip `wr-pulse` dot (`TripMatchingService::liveCorridors()`) · seat counters carry corridor data + one-shot `wr-seat-tick` on `TripSeatsUpdated` | 428 (1361) | 2026-08-06 |

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
