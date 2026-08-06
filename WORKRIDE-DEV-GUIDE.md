# WorkRide — Software Engineering Guide (World-Class Standards)

> **Purpose:** the engineering contract for anyone (human or AI agent) writing code in this
> repo. Follow it on every change. It encodes the hard-won lessons from
> `DEVELOPMENT-LOG.md` §5 ("Issues Resolved") so bugs don't recur.
> **Product spec:** `WORKRIDE-APP-GUIDE.md`. **Build state:** `DEVELOPMENT-LOG.md`.
> **Reviews of incoming ideas:** `WORKRIDE-DESIGN-REVIEWS.md`.

---

## 1. The non-negotiables

1. **Never store raw NIN.** Only `nin_hash` (SHA-256) + `nin_last4`. Same for any government ID.
2. **Money = `decimal(15,2)`.** No floats. Ever. Kobo/naira conversions happen at the edges.
3. **Atomic money moves.** Every hold/capture/refund/transfer runs in a DB transaction with
   `SELECT ... FOR UPDATE` on the wallet row and optimistic locking (`wallets.version`).
4. **Idempotency references.** Every mutation that moves money has a unique reference
   (`BOOK-{id}-HOLD`, `MDA-{workplace}-{batch}-{index}`, `EMP-{booking}-COVER`,
   `P2P-{sender}-{ts}-{rand}`). Duplicate submission must be a no-op, never a double-spend.
5. **Feature gates.** New modules ship behind `FEATURE_*` env flags (see `config/workride.php`)
   so they can never half-wire into production. Default `false` unless the feature is the
   on-boarding critical path (phone OTP, demand kit).
6. **The board shows ahead, the matcher books near.** The web board may list a 48h horizon;
   the live matching API keeps its tight window. Never pre-empt near-term seats with
   far-away bookings.
7. **Change control.** Every trust-relevant action (verification, booking, rating, SOS, wallet)
   writes an `activity_logs` row. If a funder/auditor can't replay what happened, it's not done.
8. **No comments unless they say *why*.** Code should read as prose; a comment that restates
   the line is noise. (The guide §Code style.)

---

## 2. Architecture

### 2.1 Dual-app
- **Rider PWA** — Blade + Tailwind + Alpine, `<50kb` of JS, guest-safe public pages.
- **Ops Control Tower** — admin Blade under `/admin`, the operations brain.

### 2.2 Modular monolith, event-driven
- **Services** (`app/Services`) own all business logic. Controllers are thin shells that
  validate input, call a service, return a response. A service method never returns a
  `RedirectResponse`.
- **Events** (broadcast via Reverb) notify other parts of the system and clients
  (`TripPublished`, `BookingConfirmed`, `TripLocationUpdated`, ...).
- **Jobs** (`app/Jobs`) for async: `CalculateImpactJob`, `CalculateDriverScoresJob`,
  `GenerateGtfsFeedJob`, `DeleteExpiredSelfiesJob`.
- **Middlewares** for gates: `admin`, `verified.worker`, `driver.verified`, `not.banned`.

### 2.3 Data flow for the core ride
```
TripService::publish → GeofenceService (FCT) → PricingService (fixed fare)
  → Event TripPublished (notify nearby) → BookingService::book
  → DB::transaction + lockForUpdate (decrement seats)
  → WalletService::holdForBooking (subsidy → earned → cash priority)
  → completeTrip → CalculateImpactJob → MissionService/RewardService hooks
  → RoadIntelligenceService (sensor data during active trip)
```

---

## 3. Coding standards

### 3.1 PHP
- PHP 8.3, Laravel 13. PSR-12 via Pint (`vendor\bin\pint`). Run `pint` before every commit.
- **Enums** (`app/Enums`): backed enums with `label()`. Cast them on models. When comparing a
  cast enum attribute, compare against the **enum case**, never a string:
  `$booking->status === BookingStatus::Boarded` — string comparison against a cast enum
  silently always fails.
- **Models:** relations in the model; `$casts` for enums/dates/decimals; `$attributes`
  defaults **mirroring the DB defaults** for any column whose default matters before first
  save (e.g. `wallet.version`, `road_events.is_confirmed` — a `null` in-memory attribute
  breaks optimistic locking / clustering logic).
- **Never rely on a DB default before insert.** The model attribute is `null` until a DB
  round-trip. Set it in `$attributes` when the logic reads it.
- **Naming:** service methods are verbs (`book`, `capture`, `refund`, `hold`). Money in
  naira; kobo only inside gateway adapters.

### 3.2 Blade / Alpine / Tailwind
- Design tokens live in **one** file: `resources/css/design-system.css` (Tailwind `@theme`
  + base). `app.css` imports it. Change a colour in exactly one place.
- Guest-safe layouts: any public route (road map, share card, verify pages, offline) extends
  `layouts/public` — `layouts/app` reads `auth()->user()` unconditionally and will 500 for
  guests.
- Alpine components for interactive bits (star ratings, phone OTP, install prompt, command
  palette). Event dispatch outside an `x-data` scope: use native
  `window.dispatchEvent(new CustomEvent(...))`, not `$dispatch`.

### 3.3 SQL / Eloquent
- Write queries that run on **both MySQL and SQLite** (tests run on SQLite). No
  `DAYOFWEEK()`, `SQRT()`, or other MySQL-only functions in shared code — compute in PHP.
- Prefer Eloquent relations + whereHas over raw joins where possible. For aggregates across
  a relation's relation, run ONE grouped query and attach the result as a dynamic attribute
  (nested `withCount('driver.ratingsReceived')` is **unsupported** on the query builder in
  this framework and 500s).
- Geospatial: MySQL spatial columns where cheap; portable Haversine via
  `GeofenceService::haversine()` otherwise (for probe merging use a bounding-box + PHP
  distance, not `SQRT/POW` SQL).

### 3.4 Testing
- Feature tests over unit tests for flows that cross services. 384 tests / 1230 assertions
  and rising — the suite is the safety net, don't let it rot.
- **Always** assert money invariants: no negative balances, unique `(trip_id, passenger_id)`
  bookings, idempotent webhook replays, refunds only when a COVER/hold exists.
- SQLite test DB surfaces what MySQL hides (enum/string comparison, Eloquent pluralization,
  column defaults). If it passes on SQLite it almost certainly passes on MySQL — treat
  SQLite as the stricter judge.
- Use `Storage::fake()` for uploads; assert files exist on disk for static assets (no route
  exists for `/pwa/icon-*.png` — feature-test HTTP clients 404 them).

---

## 4. Known traps (read before you refactor anything)

| Trap | Symptom | Rule |
|------|---------|------|
| Enum cast compared to string | verify/receipt/status checks "always fail" | Compare enum cases, or use `->value` when the column is a string |
| Model attribute null before insert | optimistic lock fails; clustering skips | Set `$attributes` defaults mirroring DB defaults |
| Nested `withCount('a.b')` | 500 `Call to undefined method` | One grouped query + dynamic attribute |
| `file_get_contents($storagePath)` | "Failed to open stream" | `Storage::disk('public')->get(...)` |
| MySQL-only SQL in shared code | SQLite tests fail | PHP computation or portable SQL |
| `TransientToken::delete()` | API logout crash | Guard `instanceof PersonalAccessToken` |
| `shouldRenderJsonWhen` overriding defaults | web 422s returned as redirects for JSON clients | `$request->is('api/*') \|\| $request->expectsJson()` |
| Carbon signed `diffInMinutes($now, false)` | "Book ahead" badge never shows (negative for future) | `->gt(now()->addHour())` |
| `$dispatch` outside `x-data` | ⌘K/search dead in header | Native `CustomEvent` on `window` |
| Eloquent pluralization on custom tables | `od_matrices`/`telemetries` lookups fail | Explicit `protected $table` |
| Duplicate `->index()` on same column | `migrate:fresh` fails (duplicate index name) | Declare the index once |
| Self-assignable admin role | privilege escalation | `UserRole::assignableCases()` in validation |
| `assertHeaderContaining` vs `Contains` | CSV export tests fail | Use `assertHeaderContains` |
| Blade `{{ }}` HTML-encodes apostrophes | `assertSee("You're")` fails | Assert encoded output or a plain fragment |
| QueryException driver code differs | duplicate-rating 500 on SQLite | Tolerate `23000` and `19`; pre-check `exists()` |

---

## 5. Definition of Done (the ritual — every change)

Run these **before every commit** (feature/process OR sprint milestone). From
`WORKRIDE-APP-GUIDE.md` §19.2:

| Gate | Command (Windows) | Pass criteria |
|------|-------------------|---------------|
| Format | `vendor\bin\pint` | 0 errors |
| Static analysis | `vendor\bin\phpstan analyse` | no errors outside the level-8 baseline |
| Tests | `php artisan test` | all green (428 / 1361) |
| Build | `npm run build` | no errors |
| Docs | update `DEVELOPMENT-LOG.md` | reflects this change |
| Stage | `git add <relevant files>` | only intended files |
| Inspect | `git status` / `git diff --cached` | no `.env`, no secrets |
| Commit | `git commit -m "<conventional>"` | message explains WHY |
| Tag | `git tag -a vX.Y.Z -m "..."` | only at sprint end |
| Push | `git push origin main && git push --tags` | after a tagged sprint |

Commit message = Conventional Commits: `feat|fix|test|refactor|chore|docs|perf(scope): subject`.

### PHPStan gate (level 8, baseline-managed)

`vendor\bin\phpstan analyse` runs Larastan at level 8 over `app/`. The current
level-8 findings are snapshotted in `phpstan-baseline.neon` (generated with
`vendor\bin\phpstan analyse --generate-baseline`), so the gate is **green today
and blocks new regressions**. Eloquent dynamic attributes (e.g. the
`driver_rating_avg` attached by `RatingService`) are covered by the baseline —
do not widen types or add `@phpstan-ignore` to chase zero, fix the real bug and
regenerate the baseline when a finding is genuinely resolved:

```bash
vendor\bin\phpstan analyse                     # gate (fast, cached)
vendor\bin\phpstan analyse --generate-baseline # re-snapshot after fixes
```

Burn the baseline down in priority order: `return.type` / `argument.type`
(suspected real bugs) first, then `missingType.*`, then Eloquent-magic
`property.*`/`method.nonObject`. Regenerate after each batch and keep the diff
reviewable.

---

## 6. Services quick reference (where the logic lives)

| Service | Owns |
|---------|------|
| `VerificationService` | Tier 1–3 KYC, NIN hashing, attempts/rate limit, encrypted selfie retention |
| `NimcVerificationService` / `SmileIdService` | NIN lookup + driver anti-spoof (licensed partners, cost-capped) |
| `PhoneVerificationService` | Tier-0 OTP (hash-only storage, cooldown + daily limits) |
| `TripService` | publish/start/location/complete/cancel, fleet gate, mission/reward hooks |
| `BookingService` | atomic book (FOR UPDATE), benefits gates, board/cancel/no-show, employer cover |
| `TripMatchingService` | corridor + 2km Haversine + time window; board vs live window split |
| `PricingService` | fixed anti-surge fares; `driverEarning` = fare − commission − union − insurance |
| `WalletService` | triple balance, optimistic lock, hold/capture/refund priority, idempotency |
| `WalletFundingService` / `PaystackService` | top-up + webhook verification, kobo conversion |
| `RideCreditService` | Time-Bank eligibility, repay-with-drive, overdue |
| `P2pTransferService` | transfers, fee rules, daily limits, subsidy never transferable |
| `PayoutService` | withdrawal (earned→cash, never subsidy), mock Moniepoint |
| `EmployerService` / `EmployerLedgerService` | program coverage, COVER ledger, enroll + CSV roster |
| `RewardService` | campaign award, period dedupe, budget caps, Green Points redeem |
| `MissionService` | activity observation, idempotent payout, proof review |
| `CommodityService` | buy/sell positions, shop orders (subsidy never spendable) |
| `Co2Service` | impact math: `(occupants−1)·km·0.12`, trees = CO₂/21, fuel = km·0.10·occupants |
| `RoadIntelligenceService` | RoadLab IRI, 5-within-20m-72h pothole confirmation, segment refresh, FERMA export |
| `GtfsService` / `GtfsRtService` | 7-file feed zip, wire-format GTFS-RT protobuf |
| `RoutingService` | OSRM→Google→Mapbox strategy chain, `api_cost_logs` monthly cap |
| `FleetService` | inspection gate (latest-today wins), faults, maintenance, telemetry mileage |
| `StakeholderService` | per-trip remittances, idempotent `REM-{booking}` |
| `ForecastService` | same-weekday average × event multiplier |
| `DemandService` | junction surveys, 150m probe merge, FCT-geofenced check-ins, OD matrix |
| `RatingService` | mutual ratings (once/booking), driver scoreboard aggregate |
| `GeofenceService` | FCT polygon + workplace radius + haversine |

---

## 7. Running the app

```bash
composer run dev      # server + queue:listen + pail + vite HMR
php artisan test      # full suite (SQLite)
php artisan pint      # format
vendor\bin\phpstan analyse   # static-analysis gate (level 8 + baseline)
npm run build         # production assets
php artisan gtfs:generate
php artisan db:seed   # full rich demo world (idempotent)
php artisan migrate:fresh --seed  # clean demo rebuild
```

> Never commit `.env`. On a fresh clone: `copy .env.example .env`, set `APP_KEY`, `DB_*`
> (MySQL, no password on local Laragon), then the ritual above.

---

## 8. Convention summary (repo-specific)

- Services in `app/Services`, controllers thin, money decimal(15,2), NIN hashed.
- Enums in `app/Enums`; gate new features with `config/workride.php` + `.env.example`.
- Dual-app: `layouts/app` (auth) vs `layouts/public` (guest-safe).
- Design tokens only in `design-system.css`.
- Conventional commits; tag sprints; update `DEVELOPMENT-LOG.md` before every commit.
- Tests: assert money invariants, run on SQLite, `Storage::fake` for uploads.
