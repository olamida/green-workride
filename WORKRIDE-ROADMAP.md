# WorkRide — Remaining Unimplemented Actions (Gap List)

> **Purpose:** the honest gap list — everything the spec (`WORKRIDE-APP-GUIDE.md`) and the
> reviewed ideas (`WORKRIDE-DESIGN-REVIEWS.md`) call for that is NOT yet built or NOT yet
> production-wired. Sorted by "cost to next demo/investor milestone", not by spec chapter.
> **Build state:** `DEVELOPMENT-LOG.md` §7. **How to verify each:** the "Done when" line.

---

## Priority 1 — Fundable, demo-critical, cheap

| # | Gap | Current state | Why it matters | Done when |
|---|-----|---------------|----------------|-----------|
| 1.1 | **Seeder README** (`database/seeders/README.md`) | ✅ Done — `database/seeders/README.md` ships with a per-feature demo login walkthrough (admin/driver/volunteer/passenger/rich-demo users + seeder-by-seeder tour + suite idempotency marker) | New devs / funder walkthroughs need "log in as X to see Y" | File exists with per-feature demo login walkthrough |
| 1.2 | **Google OAuth enabled** | ✅ Done — Socialite `redirect`/`callback` live; `/login` renders "Continue with Google" only under `workride.google_enabled` (env `GOOGLE_CLIENT_ID`); `AuthTest` covers on/off | Guide §3 says "AuthController (Google Sign-In, email)"; one-line sign-in is a trust + conversion lever | `GOOGLE_*` in `.env`; `/login` shows "Continue with Google"; tested |
| 1.3 | **Live seat-counter channel** | ✅ Done — `TripSeatsUpdated` broadcast on the public `trips` channel; `board-live.js` updates seats/Full/book-links and pushes into the map (since v0.17.0) | "Seats visibly filling" is the demo's aliveness moment | Reverb channel emits seat-count on `BookingConfirmed`; board subscribes |
| 1.4 | **"Leaving soon" boost** | ✅ Done — active-first sort + `leaving_soon` flag; `now` preset filters to 30 min (since v0.17.0) | Day-ahead trips bury the trip leaving in 15 min | `≤15min` sort boost on the `now` preset + test |
| 1.5 | **Demand-aware empty state** | ✅ Done — board shows "N people want this journey" + top destinations + "I need a ride" check-in link from `DemandService::demandSnapshot()` (since v0.17.0) | Ops loop: demand check-ins should seed supply prompts | Board shows "12 people want Kubwa→CBD tomorrow 7:00 — be the driver" from `demand_requests`/`forecasts` |

## Priority 2 — Production wiring (mocked → real)

| # | Gap | Current state | Why it matters | Done when |
|---|-----|---------------|----------------|-----------|
| 2.1 | **Paystack live keys + webhook** | `PaystackService` works; mode=test / synthetic fallback | Real top-ups are the revenue stream #1 | Live `PAYSTACK_*`; webhook test passes end-to-end |
| 2.2 | **SMS provider (Termii/Twilio)** | `WORKRIDE_SMS_ENABLED=false` → OTP lands in `log` channel | Tier-0 onboarding is the critical path; log-only OTP doesn't ship | `WORKRIDE_SMS_ENABLED=true` + provider key; OTP test against provider |
| 2.3 | **IdentityPass NIMC lookup** | `NimcVerificationService` ready; `USE_IDENTITYPASS=false` | Tier-2 NIN is the anti-fraud core | Licensed partner key; sandbox test approves a real hash |
| 2.4 | **Smile driver anti-spoof** | `SmileIdService` + webhook ready; `USE_SMILE=false` | Tier-3 driver gate | Sandbox webhook flow passes |
| 2.5 | **Moniepoint payouts** | `PayoutService` mocks ledger to "completed" | Drivers can't actually receive money | Real `MONIEPOINT_*`; settlement job hits the API |
| 2.6 | **Redis (GEO + queue)** | `database` driver locally; docker-compose `redis:7-alpine` defined | Guide tech stack; GEO matching + scalable queue | `CACHE_DRIVER=redis`, `QUEUE_CONNECTION=redis`, GEO indexes tested |
| 2.7 | **OSRM self-hosted** | `RoutingService` asserts free OSRM host first; `osrm` behind docker profile | 90% routing cost save | OSRM container maps Abuja; route tests run against it |
| 2.8 | **GTFS → Google submission** | Feed + RT generate; admin page shows status | "First Abuja GTFS on Google Maps" is the moat + pitch | Submitted to `transitpartnerprogram.withgoogle.com`; feed passes `feedvalidator.mobilitydata.org` |

## Priority 3 — Guide features not yet built

| # | Gap | Current state | Why it matters | Done when |
|---|-----|---------------|----------------|-----------|
| 3.1 | **USSD fallback `*347*WORK#`** | Not started (noted as "future" in spec) | Low-smartphone riders; demand bot for junctions | USSD gateway wired; "where you dey go?" flow saves to `demand_surveys` |
| 3.2 | **FCM push notifications** | Notifications go to `database` + `log` channels | "500m away" nudges need to reach a closed browser | `NotificationService` sends FCM; `UserArrivedAtPickup` fires it |
| 3.3 | **Trust float ledger** | ✅ Done — `community_trust` table + `TrustService` (idempotent credit/debit/balance) since v0.17.0; Control Tower `/admin/trust` reconciliation report (from-scratch running-balance rebuild flagging drifted `balance_after`) + CSV export since v0.18.0 | The 15% Community Trust share + ride-credit float must be auditable | Reconcilable ledger + board report shipped; remaining: pay-it-forward statement (3.11) |
| 3.4 | **Ride-credit reminders** | Debt silently ages to overdue | Overdue book needs gentle churn-recovery | Pre-due SMS/database reminder job + test |
| 3.5 | **Employer enrollment CSV self-service** | Admin uploads CSV; Form 1/2 done | MDAs should manage their own rosters | MDA-scoped upload page behind `employer_admin` |
| 3.6 | **Corridor fare config UI** | `config/workride.php` only; guide §8 wants Settings UI | Ops must tune fares without deploys | `/admin/settings` writes `workride.*` to DB/cache |
| 3.7 | **Export `maatwebsite/excel`** | CSV exports exist everywhere | FERMA + subsidy reports asked for real .xlsx | `composer require maatwebsite/excel`; admin exports xlsx |
| 3.8 | **EV lease-to-own schema seams** | DEFER per review; schema seams described | Only if EV pilot lands | `assets.propulsion`, `telemetry.battery_soc`, `lease_agreements`, `charging_stations` behind `FEATURE_EV_LEASE` |
| 3.9 | **Forecast Phase 2 ML job** | Phase-1 manual multiplier in `ForecastService` | "Predicted = avg × multiplier" is manual; ML predicts automatically | `CalculateDemandForecastJob` trains on bookings history |
| 3.10 | **Driver scorecards (rider-facing)** | ✅ Done — `DriverScore::attachLatestToTrips()` (one grouped query) + `forTrip()`; score badge on board trip cards + trip detail; `TripTest` covers shown/omitted | Riders should see driver scores pre-booking (trust) | Score badge on trip cards from `CalculateDriverScoresJob` |
| 3.11 | **Pay-it-forward statement** | No Trust-facing Time-Bank report | Board governance | Monthly report: who rode / repaid / overdue / waived |
| 3.12 | **Multi-tenant city/country** | `workplaces` single-city; guide §16 wants `country_id`/`city_id` | International expansion (Nairobi, Accra, Manila) | Migrations add country/city; GTFS + currency per city |
| 3.13 | **USSD/WhatsApp demand bot** | Rider check-in exists in-app only | Junction people without the app | Bot saves to `demand_surveys`; ops sees it in the demand calendar |
| 3.14 | **Subsidy CSR/certificates for employers** | Individual certs exist; MDA aggregate printable report thin | Employer renewals need one-click CO₂ reports | `/admin/employers/{id}/report` printable |

## Priority 4 — Documented 2028 "future" ideas (explicitly deferred)

From guide §17: AR pothole overlay, voice booking, haptics. From §12: insurance partnerships
(Leadway/Coron), union cooperative shares, FERMA data-sharing MOU. None are schema-blocked;
they're business-deal gated. Do not start them.

---

## Appendix — How this list stays honest

- Every **Priority 1–3** row should end up as a commit (with tag where it's sprint-sized)
  and a `DEVELOPMENT-LOG.md` section, at which point the row is deleted.
- If a new idea arrives, review it in `WORKRIDE-DESIGN-REVIEWS.md` **first** — then it earns
  a row here only if the verdict is ADOPT or ADAPT.
- Re-prioritize after each funder/demo round: whatever the pitch demanded moves up.
