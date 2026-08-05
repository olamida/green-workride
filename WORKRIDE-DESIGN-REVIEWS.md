# WorkRide — Design Reviews & Idea Critiques

> **Purpose:** Capture the engineering critique of proposed features *before* they become
> backlog items. Each review records the verdict (ADOPT / ADAPT / DEFER / REJECT), the
> reason, and the minimal viable implementation if adopted. This prevents the recurring
> failure mode of importing "cool ideas" that fight the schema or the money flows.
>
> **Companion docs:** `WORKRIDE-APP-GUIDE.md` (product spec), `DEVELOPMENT-LOG.md` (build
> state), `WORKRIDE-DEV-GUIDE.md` (engineering standards).
> **Status:** Living document — append a review for every new idea before it lands.

---

## Review 1 — `WORKRIDE-PROMPT-SEEDING-DATA.md` (Rich Demo Seeder Suite)

**Status:** ✅ ADOPTED & BUILT — see `DEVELOPMENT-LOG.md` §4.23 (v0.14.0) + `database/seeders/Rich*.php`.

The prompt asked for a one-command, Abuja-realistic demo world. It was adopted with
corrections. This review records *what was asked vs. what shipped* so a funder demo and the
test suite tell the same story.

### Verdict: ADOPT, with corrections

The core idea — "investor logs in and sees live vehicles moving, not an empty screen" — is
correct and delivered: 100 users, 80 trips, 554 bookings, 102 road events, 92 surveys, GTFS
regenerated, all idempotent behind an `activity_logs` completion marker.

### What changed vs. the prompt (and why)

| Prompt asked for | What shipped | Why |
|------------------|--------------|-----|
| 15 workplaces | 45 MDAs (existing `WorkplaceSeeder`) + rich demo users attached | Don't fork the canonical seed; re-seed users against it |
| 150 verifications | Rich verifications + `verification_attempts` per tier | Raw NIN is never stored; hashes + attempts mirror Sprint 3.6 KYC |
| 40 vehicles | 40 rich + 1 legacy (41 total) | Legacy `DemoUserSeeder` vehicle intentionally kept |
| 300 transactions | 200 wallet transactions + transfers + payouts ledgers | Transaction count is an invariant, not a goal |
| "100 users" | 95 demo + 5 workplace admins (`demo%@workride.ng`) | Admin/legacy demo accounts excluded from the demo namespace |
| `workride:seed-demo` command | `db:seed` + `RichGtfsSeeder` regenerates the feed inline | One command fewer; `gtfs:generate` is already on the nightly schedule |
| Live-map interpolation | 10 active trips with `current_lat/lng` + waypoints | Deterministic demo; real-time comes from the API in production |
| 20 active trips | 10 active / 22 scheduled / 40 completed / 8 cancelled | The trip board now shows day-ahead trips, so scheduled is the demo-rich state |

### Guard-rail critique (things the prompt under-specified)

1. **Idempotency is non-negotiable.** The prompt implied `migrate:fresh --seed`. We made
   `db:seed` re-runnable without `migrate:fresh` via a completion marker. Without this, a
   second `db:seed` corrupts the demo and the tests.
2. **Money invariants must be asserted.** The prompt lists counts; we assert *no negative
   wallet balances* and *every demo user has a wallet*. Counts without invariants will not
   catch a broken hold/capture/refund loop.
3. **No real PII.** The prompt already required hashed NINs — good — but we also enforce
   deterministic fake phones (`0803XXXXXXXX` range) and fake emails, so the demo world can be
   pushed to any branch safely.
4. **Faker inside seeded data is a trap.** The prompt suggested `Faker` for names. We used a
   fixed demo name set per role so leaderboards, ratings, and receipts are stable across runs
   and screenshots match the narrative.

### Residual gaps (not yet built — carry into backlog)

- `database/seeders/README.md` — a per-feature "log in as X to see Y" walkthrough was
  requested by the prompt and NOT delivered. The role guide in `WORKRIDE-USER-GUIDE.md`
  partially covers it; a seeder-specific quickstart is a cheap, high-value backlog item.
- `php artisan workride:seed-demo` convenience command — deferred; `db:seed` covers it.

---

## Review 2 — Plan-Ahead / Live-Loading Rides ("show me tomorrow's corridor")

**Status:** ✅ PARTIALLY ADOPTED (board window) — `WORKRIDE-DESIGN-REVIEWS` → `DEVELOPMENT-LOG.md` §4.24.

### The idea

Two related features (from the "trip board planning pass"):
- **Plan-ahead:** riders can see and book a seat on a day-ahead trip, not just trips leaving
  in the next 30 minutes.
- **Live-loading:** the trip board should feel alive — seats visibly filling, trips leaving
  "now", corridors pulsing.

### Verdict: ADOPT plan-ahead (done), ADAPT live-loading (partial), with constraints

**What shipped (v0.15.0):**
- Board horizon widened to 48h (`workride.board_window_minutes` = 2880) with presets
  `now`/`later`/`tomorrow`/`any` (`board_window_presets`).
- Departure-window chips + "Book ahead" / "Live now" badges + a "How to book" guide strip.
- `?window=` and `?women_only=` board filters; `women_only` defaults from the rider's profile
  preference (never a hard sort).

**The constraint that keeps this honest:**
- The *live matching API* (`findMatches()`) deliberately keeps a tight 30-minute window
  (`departure_window_minutes`). If the live API also went to 48h, near-term seats would be
  pre-empted by distant bookings and supply would evaporate. **The board shows ahead, the
  matcher books near.** This split is the design rule.

**What is still missing (carry into backlog):**
- **Predictive "leaving soon" rail:** trips that depart in ≤15 min should float to the top of
  the `now` preset regardless of creation time. Currently ordering is distance + departure;
  a cheap `LEAVE_SOON = 15min` boost is a one-line sort tweak.
- **Live seat counter:** the board polls or subscribes (Reverb channel `trips`) so seats
  visibly decrement when a colleague books. The chat/location channels already exist; a
  seat-count channel is a natural extension.
- **Demand-aware empty states:** instead of "no trips", use `demand_requests` + `forecasts`
  to show "12 people want this corridor tomorrow at 7:00 — be the driver". This closes the
  loop between the rider check-in (`/demand`) and the supply side.

### Anti-pattern warning

Do NOT auto-route passengers to "any driver leaving tomorrow" — booking is corridor +
geofence + time-window constrained for a reason (safety, verification, fixed fares). A
"smart match" that ignores the corridor breaks the anti-surge model and the trust story.

---

## Review 3 — Ride-Now-Pay-Later via Community Trust (Time-Bank)

**Status:** ✅ ADOPTED (feature-gated) — Sprint 3.5, `FEATURE_TIME_BANK`, `RideCreditService`.

### The idea

A working-class bridge: a passenger who cannot pay today rides now and "pays back" by
driving others later ("Ride now, drive later"). The Community Trust underwrites the float.

### Verdict: ADOPT, gated, with a trust-side correction

**What shipped (Sprint 3.5):**
- `ride_credits` (seats owed/repaid, due dates, overdue/waived), eligibility L2+ with a
  registered vehicle (you can only owe a seat you can repay), max 3 outstanding seats,
  `has_overdue_ride_credit` block.
- Repayment by driving: each carried passenger on a completed trip repays the oldest open
  credit, 1 seat each.
- Booking via `ride_credit` is fare-free at point of booking; the debt is the obligation.
- `WalletService` earned balance pays drivers their normal fare (commission − union −
  insurance), so the *driver never subsidizes the float* — the Trust does.

**Critique — the correction the idea needed:**
1. **It must not pay drivers from thin air.** The naive version ("free ride now, driver
   still paid") creates an infinite money printer unless the Trust balance is a real account.
   The shipped design books the fare to the passenger's *obligation* and credits the driver's
   *earned* balance — the float is real money that the Trust (MDA subsidy / grants) must fund.
2. **Eligibility must guarantee repayability.** L2+ NIN + registered vehicle is the minimum.
   A passenger with no car can never repay a driving debt; the original idea missed this and
   would have created a permanently-stranded debt book.
3. **Overdue must bite but not punish.** Blocking *new* ride-credit bookings while overdue is
   correct; it is NOT a wallet freeze or a ban — the change-control log records the flag so an
   admin can waive legitimately (hardship, fuel scarcity).
4. **Free-volunteer rides are a separate rail.** `is_free_volunteer` rides are driver-donated
   supply (Green Points), and must never be confused with Time-Bank debt. The two coexist but
   are independent: one is charity, the other is deferred reciprocity.

### Backlog additions

- Trust float ledger (a `trust_balances`/`community_trust` table that reconciles grants +
  ride-credit float + waivers) so the 15% Community Trust share has an auditable bank.
- SMS/database reminder before `due_date` (currently none — debt silently ages to overdue).
- Monthly "pay-it-forward" statement for the Trust board (who rode, who repaid, who's overdue).

---

## Review 4 — FMWASD Green Economy EV Lease-to-Own ("Electric danfo")

**Status:** 🟡 ADAPT / DEFER — design decision, not yet built. Needs a pilot partner and a fuel-price hedge.

### The idea

Convert the fleet to electric buses/vans under a lease-to-own scheme with FMWASD (Federal
Ministry of Works and Social Development) — driver pays per kilometre from earnings until the
vehicle is theirs; aligns with Nigeria's green economy push and the 2026 fuel-price crisis.

### Verdict: DEFER the hardware, ADOPT the schema seams now

The *operational* skeleton already exists (Sprint 11 `assets`, `telemetry`, `maintenance`).
EV lease-to-own is a **financing layer over the fleet**, not a new module. Do not build a
parallel EV subsystem; extend what's there.

**Critique — why not "just buy EVs":**
1. **Capex vs. the asset-light rule.** The guide's own fleet section says "Day 1: don't buy
   buses, lease." EV units cost 4–8× a danfo. Lease-to-own only works if the per-km charge is
   less than the fuel cost it displaces — which is *true at ₦1,200+/litre* but inverts the
   moment fuel drops. The scheme must be a fuel-price hedge, not a one-way bet.
2. **Charging is the bottleneck.** Abuja grid reliability + no public fast-charging → the
   "asset" needs a `charging_station` concept (location, kW, occupancy, scheduled slots) or
   the fleet grounds itself. Add it to the schema when a station is in the pilot.
3. **Telemetry is already the sensor story.** `OBD2` → EV is a change of `asset_type` +
   battery telemetry columns; the driver-app inspection and maintenance scheduler carry over.
   Keep one fleet table, add `propulsion` + `battery_soc`/`range_km` to `telemetry`.
4. **Risk of "green theater".** Green Credits only pay if the CO₂ saving is *measurable*
   (Sprint 6 impact + certificates already compute CO₂ per ride). An EV lease must key its
   monthly charge to `ImpactStat` data or it's marketing, not finance.

### Minimal viable adoption (if a pilot lands)

- Migration: `assets.propulsion` ENUM(`ice`, `hybrid`, `ev`); `telemetry.battery_soc`,
  `telemetry.range_km`.
- New table `lease_agreements` (`asset_id`, `driver_id`, `total_ngn`, `paid_ngn`, `per_km_ngn`,
  `fuel_baseline_ngn_per_litre`, `status`, `next_due_at`) + a `LeaseService` that deducts
  `per_km_ngn × trip_distance` from driver earnings into the lease ledger on `completeTrip`.
- `charging_stations` table (location/geofence, kW, slots, `is_available`) + `/fleet` page
  "charge here" section.
- **Gate it behind a `FEATURE_EV_LEASE` env flag** like every other insurer/portfolio
  switch, so it never ships half-wired.

### Recommendation

Do NOT start the EV build now. The correct order is: (1) pilot the asset-light lease of
internal-combustion 18-seaters (Sprint 4/11 already models this), (2) prove per-km economics
in `telemetry`, (3) then swap propulsion. The schema seams above are cheap and additive; the
hardware decision is a business call for the pilot, not a schema call today.

---

## How to add a new review

1. Copy the heading block below.
2. Fill **The idea** (2–3 sentences, in the proposer's voice — no reframing yet).
3. Fill **Verdict** as ADOPT / ADAPT / DEFER / REJECT with a one-line reason.
4. Critique: (a) what the idea gets right, (b) the schema/money/trust constraint it misses,
   (c) the minimal viable build. Call out any feature-gate needed.
5. List **backlog additions** so the idea survives into the roadmap.

```
## Review N — <Title>

**Status:** 🟡 <ADOPT | ADAPT | DEFER | REJECT>

### The idea

### Verdict

### Critique

### Backlog additions
```
