# WorkRide — Rider & Operations User Guide

> **What this is:** the plain-language, role-based how-to for everyone touching the platform —
> passengers, drivers, volunteers, workplace admins, and the Control Tower ops team.
> **Product spec:** `WORKRIDE-APP-GUIDE.md`. **Engineering state:** `DEVELOPMENT-LOG.md`.
> **Quick demo accounts** (password `demo1234`): `driver@workride.ng`,
> `volunteer@workride.ng`, `passenger@workride.ng`. **Admin:** `admin@workride.ng` / `admin1234`.

---

## 1. The 10-second story

WorkRide is **not** ride-hailing. It's a corridor-based staff mobility network:

1. **Fixed corridors** (Kubwa→CBD, Nyanya→Idu, Lugbe→CBD) with **fixed anti-surge fares** —
   no surge pricing, ever.
2. **Verified people** — your workplace ID, NIN hash (never the raw number), and driver
   documents gate who can ride and who can drive.
3. **Three payment rails** — cash, wallet (Paystack top-up), and MDA **subsidy credits** that
   work like palliative vouchers with a full audit trail.
4. **Free volunteer rides** — verified colleagues who drive empty seats for Green Points, not
   profit.
5. **You are the sensor** — every trip quietly reports potholes (Z-axis) so FERMA gets a real
   road map. It's anonymous and only runs while you're on an active trip.

---

## 2. Getting in (Tier-0 phone verification)

New accounts are **phone-first**, not KYC-first — so a worker can book a seat within minutes
of signing up.

1. **Register** with your real phone number.
2. **Verify your phone** (6-digit OTP by SMS/database). This unlocks **booking** immediately.
3. Book with **wallet or cash** right away. The "benefits" below unlock at Level 1+.

> **What phone-verification alone does NOT unlock** (it needs Level 1 workplace ID): subsidy
> credits, ride-credit (Time-Bank), free-volunteer rides, women-only rides, employer
> coverage, and publishing trips. Rationale: driving and spending other people's subsidy is a
> trust act; booking your own seat for cash is not.

**Account levels**

| Level | How to get it | Unlocks |
|-------|---------------|---------|
| 0 | Register | None yet |
| Tier-0 | Phone OTP | Book with wallet/cash |
| 1 | Workplace ID upload (auto-approved on staff-liveness pass, or admin review) | Subsidy, ride-credit, free rides, women-only, employer coverage, publish free volunteer rides |
| 2 | NIN check (licensed partner; hash only stored) | Higher-value transfers, ride-credit eligibility |
| 3 | Driver documents + vehicle | Publish paid trips, live location |

---

## 3. Passenger guide

### 3.1 Find a ride
- Open **Trips** from the top nav.
- Pick your **corridor chip** (Kubwa→CBD, etc.).
- Filter by **departure window**: Leaving soon / Later today / Tomorrow / Anytime (48h board).
- The board shows seats, fixed fare, driver + rating stars, and badges (Live now, Book ahead,
  Women-only, Free).

### 3.2 Book a seat
- **View & book →** on a trip card.
- Choose pickup (defaults to your location / a waypoint) and payment method:
  - **Wallet** — cash balance; hold is taken at booking, captured when you board.
  - **Cash** — pay the driver; logged to their cash sheet.
  - **Subsidy credits** — MDA-funded; debited first, auditable end-to-end.
  - **Ride credit (Time-Bank)** — ride now, repay by driving later (Level 2+, registered
    vehicle, max 3 outstanding seats).
  - **Free volunteer** — no charge; say thank you to the driver.
- Seats are decremented atomically — a seat is never double-sold.

### 3.3 During the ride
- **Live chat** with the trip on the trip page (Reverb).
- **Live driver location** updates every ~15s once the trip starts.
- The driver gets a 500m "you're here" nudge via change-control when you reach your pickup.
- If your trip is active, your phone's sensors are quietly logging road conditions —
  **anonymous**, only lat/lng + severity, never your name on the public map.

### 3.4 After the ride
- **Rate** the driver (1–5 stars + optional note) on My Rides — once per booking, audited.
- Watch your **impact** grow on **Impact** (CO₂ saved, fuel saved, trees equivalent, Green
  Level).
- Grab a **QR-verifiable certificate** (`/impact/certificate/co2`) for your commute claims.

### 3.5 Your wallet
- **Wallet** page: three balances — Cash, Subsidy credits, Earned.
- **Top up** with Paystack (₦100–₦1M).
- **Send money** to verified colleagues (1% cash fee, free from earned; never subsidy).
- **Withdraw to bank** (earned first, then cash; never subsidy).
- **Monthly statement** receipt for salary-deduction proof.

### 3.6 Safety
- **Share trip** page — a public read-only card of your ride to send your family (no live
  location).
- **SOS** button on an active trip you're part of — writes an audited alert the Control Tower
  sees instantly.
- **Emergency contact** set on Profile & safety (never shown to other riders).
- **Women-only rides** — opt-in board filter (defaults from your profile preference; never a
  hard sort). Non-female riders see a block panel on women-only trips.

### 3.7 Report a pothole (manual, optional)
- Road sensing is automatic during active trips. You can also report on the **Road map**.

### 3.8 Tell us what's needed — Demand check-in
- On **Demand**, check in "I'm at Berger Junction, need ride to Secretariat, 2 people" even if
  no driver exists yet. This feeds the OD matrix and future supply plan — it's how the BRT
  pre-design works, done with phones instead of consultants.

---

## 4. Driver guide (Level 3)

1. **Verify to Level 3** — vehicle documents + driver license. This is the paid-driver gate.
2. **Publish a trip** (Trips → Publish): corridor, route name, departure time, seats, fare.
   Fares are fixed per corridor (max ₦800 anti-surge); volunteer rides are ₦0.
   - Trips must be inside the FCT geofence.
   - If fleet mode is on, you must pass a **pre-trip inspection** on **My fleet** before you
     can publish (photos + pass/fail). Failed inspection auto-opens a fault ticket.
3. **On departure**, Start the trip. Your live location updates every ~15s.
4. **Board** each passenger as they arrive (captures the fare hold). **No-show** after 50%
   capture rule.
5. **Complete** the trip — impact is credited, rewards/missions fire, and each passenger
   repays one of your Time-Bank seats if you owe any.
6. **Cash rides** are logged to your cash sheet; reconcile in your wallet.
7. **Withdraw earnings** (earned balance first). Driver earnings = fare − 10% commission − 5%
   union fee − ₦100 insurance.
8. **Stay green** — every confirmed pothole you report and every volunteer seat earns Green
   Points (redeemable at `/rewards`).

**Driver scoreboard:** the Control Tower ranks drivers weekly (completed rides, Green Points,
average rating, pothole reports). Keep ratings high — stars show on your trip cards.

---

## 5. Volunteer guide (Level 1+)

- Publish **free volunteer rides only** (`is_free_volunteer` = true, fare ₦0). You can't set a
  fare — that's the point: it bootstraps supply during the fuel crisis.
- Earn **Green Points** per passenger carried + **fuel discount coupons** via campaigns.
- Track your impact on **Impact** — your free seats are the strongest CSR story in the pitch.

---

## 6. Workplace admin guide (MDA / employer)

### 6.1 Subsidy (palliative credits)
- **Admin → Subsidies**: see per-MDA totals (issued, spent, staff funded).
- **Bulk credit** a CSV (`email,amount`) — each row is idempotent and audit-linked
  (`MDA-{workplace}-{batch}-{index}`), so a re-upload never double-credits.
- Every subsidy spend is a `subsidy_credit` transaction with a **QR-verifiable receipt** —
  your palliative becomes a dashboard, not a cash diversion.

### 6.2 Employer mobility programs (Forms 1 & 2)
- **Form 1 — self-request:** staff find your open program (`Profile/Employer`), join, and you
  approve in **Admin → Employers → Pending**. Approval grants them Level 1 + phone-verified.
- **Form 2 — roster upload:** upload a CSV roster (`email, name, phone, employee_id`). Unknown
  emails are **auto-created** as accounts with a temporary password + welcome email, then
  granted Level 1.
- **Coverage models:** full (covers the passenger's hold), one-way (50%), percent
  (`coverage_pct`), capped (`cap_per_ride_ngn`). Coverage is charged to your **employer
  wallet** at boarding (`COVER` ledger), refunded on cancellation.
- **Fund the wallet** from Admin → Employers → Fund. Watch utilization on the Business page.

---

## 7. Operations Control Tower guide (Admin)

### 7.1 Dashboard (`/admin`)
KPIs: trips today, active drivers, bookings, fuel saved, CO₂ saved, confirmed potholes, GTFS
last generated, MRR, subsidy issued. Plus SOS safety alerts panel.

### 7.2 Day-to-day queue
| Page | What you do there |
|------|-------------------|
| **Verifications** | Approve/reject Level 1/2/3; "Needs review" queue for low liveness scores; KYC cost per provider |
| **Users** | Search, filter by level, ban/unban (admins protected), reset password |
| **Employers** | CRUD employers, fund wallets, enroll staff, approve/reject/review pending members, per-employer vehicles |
| **Subsidies** | Bulk credit CSVs, MDA utilization |
| **Trips / Bookings** | Filter corridor/status/free/paid, view on map, cancel + refund |
| **Road Intelligence** | Heatmap by date/severity, confirm potholes, **export CSV for FERMA** |
| **Business** | Revenue, MRR, corridor revenue, subsidy utilization + 3 CSV exports (transactions, settlements, subsidy) |
| **Ratings** | Driver scoreboard + recent mutual ratings |
| **Rewards / Missions** | Create campaigns & missions, review photo-proof submissions, approve payouts |
| **GTFS** | Feed status, download `gtfs.zip`, regenerate, Google submission state |

### 7.3 Ops & demand planning (Sprint 11)
- **Demand** — junction counts (manual surveys), pending rider check-ins, OD matrix. This is
  your BRT pre-design field kit: "₦50k interns + phones vs $100k consultants."
- **Fleet** — assets, inspections, faults, maintenance schedules (5,000km preventive +
  monthly), telemetry intake; ground/repair/resolve.
- **Forecasts** — the demand calendar. Enter events (FAAC, Juma'a, NYSC, rain season); the
  service multiplies the same-weekday average (e.g. Friday Juma'a + FAAC → 0.7 on CBD, extra
  buses after 2:30pm on mosque corridors). Saves 30% fuel by not deploying empty buses.
- **Stakeholders** — remittance ledger: per-trip `driver − commission − union − insurance`
  splits, settle due union remittances.
- **Driver scores** — run the weekly snapshot (rides, Green Points, rating, pothole reports).

### 7.4 Governance
- The **15% Community Trust** share and **40% workforce** spend are documented in the spec;
  the Business page + receipts are the audit trail for the quarterly board review.

---

## 8. Feature flags (what's on/off by default)

| Feature | Env flag | Default | Notes |
|---------|----------|---------|-------|
| Phone OTP onboarding | `FEATURE_PHONE_VERIFICATION` | **on** | Tier-0 gate |
| Demand field kit | `FEATURE_DEMAND` | **on** | Rider check-in + surveys |
| Time-Bank ride credits | `FEATURE_TIME_BANK` | off | On when pilot MDA onboarded |
| Employer programs | `FEATURE_EMPLOYER_PROGRAMS` | off | Enable for pilot MDA |
| Rewards / Green Points | `FEATURE_REWARDS` | off | |
| Commodities / Shop | `FEATURE_COMMODITIES` | off | Wallet → gold/rice/maize/fuel |
| Missions | `FEATURE_MISSIONS` | off | Sponsor-defined activities |
| Tiered KYC (liveness) | `FEATURE_LIVENESS` + `USE_IDENTITYPASS`/`USE_SMILE` | off | Until licensed partners configured |
| Fleet ops | `FEATURE_FLEET` | off | Driver pre-trip inspection |
| Stakeholder remittances | `FEATURE_STAKEHOLDER_REMITTANCES` | off | |
| Forecasting | `FEATURE_FORECASTING` | off | |
| Animated brand cards | `WORKRIDE_ANIMATIONS` | off | Decorative SVGs gated until reviewed |

---

## 9. Public surfaces (no login needed)

- `/` landing + KPI strip
- `/road/map` road condition heatmap (Green/Yellow/Red IRI) + worst segments table
- `/gtfs/gtfs.zip` — the GTFS feed (Abuja's first — Google Maps searchable once submitted)
- `/gtfs/gtfs-rt/vehicle_positions.pb` — live vehicle positions (GTFS-RT)
- `/trips/{trip}/share` — public share card
- `/impact/verify/{user}/{type}` — certificate verification (QR target)
- `/receipts/verify/{type}/{reference}` — receipt verification (QR target)
- `/offline` — offline fallback page (PWA)

---

## 10. Quickstart for a pitch demo

1. `php artisan migrate:fresh --seed` — builds the full rich demo world (100 users, 80 trips,
   554 bookings, pothole clusters, surveys).
2. Log in as **`passenger@workride.ng`** → book a seat on a day-ahead trip (shows the 48h
   board + Book ahead badge), then view wallet (cash + ₦15,000 subsidy) + impact certificate.
3. Log in as **`driver@workride.ng`** → publish a trip (Coaster ABJ-849-KJ), start it, board
   the passenger, complete it → watch earned balance + receipt.
4. Open **`/admin`** → Business (revenue charts), Road Intelligence (FERMA export), Demand
   (OD matrix), Fleet, Driver scores.
5. Scan any receipt/certificate QR on the public verify URL — that's the anti-fraud audit
   trail funders ask about.
