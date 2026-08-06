# WorkRide Seeders — What Each One Creates & How to Demo It

> Run everything with `php artisan migrate:fresh --seed` (or `php artisan db:seed` on a
> fresh DB). Every seeder is **idempotent** — re-running is safe. The rich demo suite
> writes a single `activity_logs` completion marker (`rich_suite_seeded`) so it never
> duplicates itself.

## Quick-start demo logins

| Email | Password | Role | See |
|-------|----------|------|-----|
| `admin@workride.ng` | `admin1234` | Super admin | Control Tower `/admin` (verifications, GTFS, road, business, fleet, forecasts, trust, missions…) |
| `driver@workride.ng` | `demo1234` | L3 paid driver | Publish a paid trip, live location, earnings receipt |
| `volunteer@workride.ng` | `demo1234` | Volunteer (L1) | Publish a **free** volunteer ride (10 Green Points) |
| `passenger@workride.ng` | `demo1234` | Passenger (L1) | Book with ₦3,200 cash + ₦15,000 subsidy, impact certificates |
| `demo001@workride.ng` … | `demo1234` | 100 rich-demo users | The full funder walkthrough world (see below) |

Admin password is configurable via `WORKRIDE_ADMIN_EMAIL` / `WORKRIDE_ADMIN_PASSWORD`;
demo password via `WORKRIDE_DEMO_PASSWORD`. Never use these credentials in production.

## Seeder-by-seeder walkthrough

| Seeder | Creates | Demo moment |
|--------|---------|-------------|
| `WorkplaceSeeder` | 45 FCT MDAs (CBD/Idu/Garki zones, lat/lng + geofence) | Registration "Your MDAs" dropdown |
| `GtfsStopSeeder` | 53 GTFS catalog stops across the 3 corridors | `/admin/gtfs` feed generation uses them |
| `AdminUserSeeder` | The super admin (`admin@workride.ng`) | First Control Tower login |
| `DemoUserSeeder` | 3 pitch accounts (driver/volunteer/passenger) | Funding demo: booking, subsidy, impact |
| `Sprint8DemoSeeder` | FMF employer + ₦2M mobility wallet + 3 enrolled staff + reward campaigns + commodities (Gold/Rice/Maize/Fuel) | Employer mobility + rewards + shop demo |
| `DemoMissionSeeder` | Pothole-weather sponsor brief + sample rewards | Missions hub demo |
| `DemoOpsSeeder` | A few junctions, one union, one leased asset + inspection, one forecast event | Ops pages light demo (gated by feature flags) |
| `JunctionSeeder` | 45 Abuja waiting junctions (Kubwa/Nyanya/Lugbe corridors) | `/demand` check-in dropdown |
| `RichUserSeeder` | 100 users: 30 L3 drivers, 15 L3 both, 10 L1 volunteers, 40 passengers, 5 workplace admins; phone-verified, NIN-hashed | Bulk roster realism |
| `RichVerificationSeeder` | Approvals per tier + verification attempts | Admin verifications queue |
| `RichVehicleSeeder` | 40 vehicles (coasters/staff buses/danfos/sedans) | Trip publish vehicle picker |
| `RichWalletSeeder` | 100 wallets + 200 top-up/subsidy/earned transactions | Wallet page + business KPIs |
| `RichTripSeeder` | 80 trips (40 completed / 10 active / 22 scheduled / 8 cancelled) with waypoints | Board + map + GTFS regeneration |
| `RichBookingSeeder` | 554 bookings, seat-safe, no duplicate pairs, wallet/cash/subsidy/ride-credit/free | My Rides, receipts, business dashboard |
| `RichRideCreditSeeder` | 30 Time-Bank credits (owed/repaid/overdue/waived) | Time-Bank panels + Trust float |
| `RichTransferSeeder` | 40 P2P transfers + 20 payouts with ledger rows | Wallet send/withdraw history |
| `RichRoadSeeder` | 102 road events (72 raw + 6 confirmed pothole clusters) + 20 IRI segments | `/road/map` heatmap + FERMA export |
| `RichDemandSeeder` | 92 junction counts, 40 check-ins, 25 OD surveys, 30 probe points, 11 OD-matrix rows | `/admin/ops/demand` demand calendar |
| `RichGtfsSeeder` | Regenerates `gtfs.zip` (171 stops, 3 routes, 32 trips) | `/gtfs/gtfs.zip` download + Google submission |
| `RichChatImpactSeeder` | 120 chat messages + 70 impact stats — **writes the suite marker** | Chat, impact leaderboards, certificates |

## How to demo in 5 minutes (funder walkthrough)

1. `php artisan migrate:fresh --seed`
2. Sign in as **passenger@workride.ng** → `/trips` — see corridor chips with live stats, tap a
   scheduled trip, book a seat with **subsidy**.
3. Sign in as **driver@workride.ng** → `/trips` → "Publish a trip" → confirm the fixed fare
   appears; start it and watch the board card go **Live now**.
4. Open `/impact` as a passenger → download the QR-verifiable CO₂ certificate → scan it.
5. Open `/admin` as **admin@workride.ng** → Business (KPIs + exports) → Road Intelligence
   (heatmap + FERMA CSV) → GTFS (download `gtfs.zip`) → Trust (float ledger reconciliation).
6. Ride a rich demo seat: log in as `demo001@workride.ng`, book on a **scheduled** rich trip
   (`/trips?window=any`), then cancel it — the seat frees instantly and the live counter ticks.

## Feature-gated seeders

`Sprint8DemoSeeder`, `DemoMissionSeeder` and `DemoOpsSeeder` no-op when their feature flags
are off (`FEATURE_EMPLOYER_PROGRAMS`, `FEATURE_MISSIONS`, and the fleet/stakeholder/forecast
flags respectively). The rich suite (`Rich*`) is always on.
