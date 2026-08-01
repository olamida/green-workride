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

## 2. Current Status (Phase: Foundation / Sprint 3 Complete)

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
| Tests | ✅ 90 feature tests passing (auth, verification, admin, trips, bookings, chat, wallet, subsidy) |

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
php artisan ide-helper:generate  # refresh IDE autocomplete
```

### Useful endpoints
| Path | Purpose |
|------|---------|
| `/` | Landing page (redirects to `/dashboard` when logged in) |
| `/dashboard` | Rider dashboard (wallet, verification, impact) |
| `/verify` | Level 1 workplace ID + Level 2 NIN submission |
| `/admin` | Ops Control Tower — dashboard, verifications, users, workplaces |
| `/telescope` | Debug dashboard (requests, queries, jobs, mail) |
| `/api/v1/auth/*` | Sanctum API — register, login, me, logout |
| `/api/v1/verifications/*` | Sanctum API — submit workplace/NIN verification |

### Seeded admin
`admin@workride.ng` / `admin1234` (via `config/workride.php` → `WORKRIDE_ADMIN_EMAIL` / `WORKRIDE_ADMIN_PASSWORD`).

---

## 7. Roadmap (from the guide — 8 sprints)

| Sprint | Scope | Status |
|--------|-------|--------|
| Sprint 1 (Wk 1-2) | Auth + Verification (NDPR compliant, Google sign-in) | ✅ Complete |
| Sprint 2 (Wk 3) | Trip + Booking atomic + Reverb chat | ✅ Complete |
| Sprint 3 (Wk 4) | Wallet dual balance + Paystack + subsidy bulk credit | ✅ Complete |
| Sprint 4 (Wk 5) | GTFS Publisher → submit to Google | ⏳ Next |
| Sprint 5 (Wk 6) | Road Sensor (`useRoadSensor.js`) + heatmap | ⏳ |
| Sprint 6 (Wk 7) | PWA award UI + impact certificates | ⏳ |
| Sprint 7 (Wk 8) | Business dashboard + receipts + exports | ⏳ |

### Immediate next steps
1. Sprint 4: GTFS Publisher — `GtfsService` generating agency/stops/routes/trips/stop_times/calendar/shapes → `gtfs.zip`, nightly job, validation page
2. Enable Redis (GEO + queue) per the guide's tech stack
3. Add `maatwebsite/excel` for FERMA/CSV exports when needed
4. Add the v3.0/v4.0 operations tables (demand surveys, forecasts, assets, maintenance) as a follow-up schema pass

---

## 7.1 Version History (Git Tags)

> Policy per guide §19: each sprint ends in **one milestone commit + one annotated tag**, and
> every feature/process implementation passes the DoD ritual before its own commit.
> Update this table on every phase-end commit. Full workflow: `WORKRIDE-APP-GUIDE.md` §19.

| Tag | Sprint | State | Tests (assertions) | Date |
|-----|--------|-------|--------------------|------|
| `v0.2.0` | Baseline — Foundation (Sprint 1 + 2) | Scaffold + auth/verification/control tower + trips/bookings/chat | 71 (222) | 2026-08-01 |
| `v0.3.0` | Sprint 3 — Wallet + Top-up + Subsidy | Paystack top-up + webhook + wallet page + subsidy bulk credit (CSV) + MDA dashboard | 90 (269) | 2026-08-01 |

---

## 8. Key Conventions & Notes

- App logic lives in services (`app/Services`) — keep controllers thin
- Money = `decimal(15,2)`; never store raw NIN — only SHA256 hash + last 4
- Enums in `app/Enums` (guide defines 10: UserRole, VerificationLevel, TripStatus, BookingStatus, Corridor, PaymentMethod, TransactionType, RoadEventType, RoadCondition, VehicleType)
- Registration only allows `UserRole::assignableCases()` (passenger/driver/both/volunteer) — admin roles come from the Control Tower
- Dual-app: Blade+Tailwind+Alpine Rider PWA (public) + Filament-style Ops Control Tower
- Design system: Forest Green `#2E7D32`, Gold `#FBC02D`, Slate `#0F172A`, Paper `#F6F9F6`; Sora/Inter/JetBrains Mono; 8px grid
- Git: Conventional Commits (`feat|fix|test|refactor|chore|docs|perf(scope): subject`); never stage `.env`/secrets; tag each sprint (`v0.X.0`); update this log before every commit
- Git cadence: commit after **every feature/process implementation** that passes the DoD ritual (pint → test → build → docs → stage → commit), and one milestone commit + tag (`v0.X.0`) at each sprint boundary — per guide §19
