# AI CODER PROMPT: Generate Complete Seeding Data for WorkRide Testing
### Test Live Vehicles, Passengers, Bookings, Wallet, Time-Bank, Verification, GTFS, Road Intelligence

> **Goal:** One command `php artisan db:seed` must populate Abuja-realistic data to demo every feature live: 3 corridors moving, 20 live vehicles on map, bookings, wallet flows, ride credits, P2P transfers, verifications, road potholes, GTFS feed valid, Control Tower charts full
> **Priority:** URGENT — Sprint 4.5 — Blocks QA, investor demo, FCTA presentation
> **Compliance:** Use fake data only (faker), hash NINs, no real phone numbers except test format 080XXXXXXXX, NDPR safe

---

## ROLE
You are a Senior Laravel Seeder Architect + Abuja Mobility Expert. You have seeded Uber, Bolt, Quick Ride, Digital Matatus. You know how to create realistic, interlinked data that makes demo feel live.

## CURRENT SCHEMA (from WORKRIDE-APP-GUIDE v4.0 + DEVELOPMENT-LOG v0.4.0 + Time-Bank + Verification prompts)

**Core:** users, workplaces, verifications, verification_attempts, vehicles, trips, trip_waypoints, bookings, wallets, transactions, chat_messages, impact_stats, road_events, road_segments, activity_logs, gtfs_stops, gtfs_routes, api_cost_logs

**New from v4.0 Demand Research:** junctions, demand_surveys, od_surveys, demand_requests, probe_demand_points, od_matrix, forecasts, duty_roster, assets, maintenance_logs, fuel_logs, stakeholder_remittances

**New from Time-Bank:** ride_credits, p2p_transfers, payouts

**You must seed ALL tables with relationships intact.**

---

## SEEDING REQUIREMENTS — ABUJA REALISTIC DATA:

### 1. Workplaces (15) — `WorkplaceSeeder` — All major MDAs where staff commute daily
```
Federal Secretariat Phase 1, Shehu Shagari Way, Central Area lat 9.0579 lng 7.4898 radius 500m zone cbd
Federal Secretariat Phase 2 lat 9.0585 lng 7.4905 zone cbd
Idu Industrial Area (Idu Train Station + Factories) lat 9.0522 lng 7.3245 zone idu
Wuse Zone 5 (Federal Ministries) lat 9.0732 lng 7.4478 zone wuse
Maitama Ministry Complex (Ministry of Justice, Finance) lat 9.0890 lng 7.4890 zone maitama
Garki Area 11 (FCTA, Area 11) lat 9.0317 lng 7.4832 zone garki
Central Business District (CBN, NNPC Tower) lat 9.0662 lng 7.4832 zone cbd
National Assembly Complex, Three Arms Zone lat 9.0680 lng 7.5120 zone cbd
Transcorp Hilton Area / Aso Drive (Presidency) lat 9.0815 lng 7.4950 zone maitama
Jabi Business District (Shoprite, Jabi Lake Mall) lat 9.0650 lng 7.4200 zone jabi
Kuje Area Council Secretariat lat 8.8800 lng 7.2300 zone kuje
Gwagwalada Specialist Hospital / University of Abuja lat 8.9400 lng 7.0800 zone gwagwalada
Bwari Area Council (Law School, JAMB) lat 9.2833 lng 7.3800 zone bwari
Kuje Industrial Layout lat 8.8700 lng 7.2500 zone kuje
Airport Road / Nnamdi Azikiwe Airport Staff Quarters lat 9.0060 lng 7.2700 zone lugbe
```
Include: name, zone ENUM (cbd, wuse, maitama, garki, jabi, idu, kuje, gwagwalada, bwari, lugbe, kubwa, nyanya), lat/lng, geofence_radius 300-800m, contact_person, is_active true

### 2. Junctions (40+) — `JunctionSeeder` — MUST INCLUDE ALL HIGH-TRAFFIC PASSENGER HOTSPOTS (for demand surveys + GTFS stops + live map)

**These are the real Abuja & environs where thousands wait daily 5:30-9am and 4-8pm. Seed ALL:**

**A. Kubwa Axis (Kubwa-CBD Corridor - your primary corridor, heaviest traffic):**
```
Kubwa Junction / Kubwa Village Market lat 9.1500 lng 7.3333 corridor kubwa_cbd — 2000+ passengers daily
Kubwa FHA Junction lat 9.1650 lng 7.3300 corridor kubwa_cbd
Kubwa Second Gate (2nd Gate) lat 9.1550 lng 7.3400 corridor kubwa_cbd
Dutse Alhaji Junction lat 9.1200 lng 7.3800 corridor kubwa_cbd
Dutse Baupma Junction lat 9.1100 lng 7.3900 corridor kubwa_cbd
Bwari Junction (from Bwari to Kubwa) lat 9.2833 lng 7.3800 corridor kubwa_cbd
Dei-Dei Junction / Dei-Dei Market lat 9.1100 lng 7.2800 corridor kubwa_cbd
```

**B. Nyanya-Mararaba Axis (Nyanya-Idu Corridor - 2nd heaviest, Nasarawa commuters):**
```
Nyanya Under-Bridge (main terminal) lat 8.9800 lng 7.5800 corridor nyanya_idu — 5000+ daily
Mararaba Junction (Mararaba) lat 8.9700 lng 7.5900 corridor nyanya_idu
Masaka Junction (Masaka, Nasarawa) lat 8.9500 lng 7.6500 corridor nyanya_idu
Keffi Road / One Man Village lat 8.9000 lng 7.7000 corridor nyanya_idu
Karshi Junction lat 8.8500 lng 7.5500 corridor nyanya_idu
Karu Junction lat 8.9900 lng 7.5700 corridor nyanya_idu
Jikwoyi Junction lat 8.9700 lng 7.5600 corridor nyanya_idu
Kurudu Junction lat 8.9600 lng 7.5400 corridor nyanya_idu
Orozo Junction lat 8.9300 lng 7.5200 corridor nyanya_idu
```

**C. Lugbe-Airport Road Axis (Lugbe-CBD Corridor - Airport Road, fast growing):**
```
Lugbe Junction / Lugbe Across lat 8.9600 lng 7.3800 corridor lugbe_cbd — 1500+ daily
Lugbe Federal Housing (FHA Lugbe) lat 8.9500 lng 7.3700 corridor lugbe_cbd
Lugbe Shoprite / Total Filling Station lat 8.9550 lng 7.3750 corridor lugbe_cbd
Kuje Junction (on Airport Road) lat 8.8800 lng 7.2300 corridor lugbe_cbd
Gwagwalada Junction (Gwagwalada) lat 8.9400 lng 7.0800 corridor lugbe_cbd — University of Abuja students
Giri Junction (Gwagwalada Road) lat 8.9200 lng 7.1500 corridor lugbe_cbd
Airport Toll Gate / Bill Clinton Drive lat 9.0060 lng 7.2700 corridor lugbe_cbd
Aco Estate Junction lat 8.9450 lng 7.3600 corridor lugbe_cbd
Pyakasa Junction lat 8.9350 lng 7.3500 corridor lugbe_cbd
```

**D. Zuba-Suleja Axis (North-West entry, Niger State commuters):**
```
Zuba Junction (Zuba) lat 9.1000 lng 7.2100 corridor kubwa_cbd — 1000+ daily from Niger
Suleja Junction (Suleja, Niger State) lat 9.1800 lng 7.1700 corridor kubwa_cbd — major origin
Madalla Junction (Madalla) lat 9.1300 lng 7.2000 corridor kubwa_cbd
Dei-Dei to Zuba Road / Dakwa Junction lat 9.1200 lng 7.2500 corridor kubwa_cbd
Tafa Junction (Tafa, Kaduna Road) lat 9.2500 lng 7.2500 corridor kubwa_cbd
```

**E. CBD & City Center (Destinations - where passengers alight):**
```
Berger Junction (Wuse, major bus stop) lat 9.0820 lng 7.4450 corridor kubwa_cbd — all corridors converge
Banex Junction (Wuse 2) lat 9.0800 lng 7.4300 corridor garki_wuse
Wuse Market Junction lat 9.0630 lng 7.4530 corridor garki_wuse
Area 1 Junction / Area 1 Shopping Complex lat 9.0430 lng 7.4850 corridor garki_wuse
Area 3 Junction lat 9.0350 lng 7.4900 corridor garki_wuse
Apo Junction / Apo Mechanic Village lat 8.9900 lng 7.5000 corridor lugbe_cbd
Asokoro Junction / AYA Junction lat 9.0500 lng 7.5200 corridor nyanya_idu — major interchange
Mabushi Junction lat 9.0700 lng 7.4300 corridor kubwa_cbd
Jabi Lake Junction / Jabi Motor Park lat 9.0650 lng 7.4200 corridor kubwa_cbd
Gwarimpa Gate / 3rd Gate Gwarimpa lat 9.1000 lng 7.4100 corridor kubwa_cbd — 2000+ residents
Kado Junction lat 9.0900 lng 7.4200 corridor kubwa_cbd
Utako Junction / Utako Market lat 9.0800 lng 7.4350 corridor kubwa_cbd
Gudu Junction lat 9.0200 lng 7.4900 corridor garki_wuse
Durumi Junction lat 9.0300 lng 7.4600 corridor garki_wuse
Galadimawa Junction lat 8.9700 lng 7.4200 corridor lugbe_cbd
Lokogoma Junction lat 8.9600 lng 7.4500 corridor lugbe_cbd
```

**F. Other High-Density Areas:**
```
Mpape Junction (Mpape) lat 9.0900 lng 7.5000 corridor kubwa_cbd — quarry workers
Karmo Junction lat 9.0400 lng 7.3800 corridor kubwa_cbd
Idu Junction / Idu Train Station lat 9.0522 lng 7.3245 corridor nyanya_idu — train commuters
Life Camp Junction lat 9.0800 lng 7.4000 corridor kubwa_cbd
Karsana / Kubwa Express Road lat 9.1300 lng 7.3500 corridor kubwa_cbd
```

Include for each junction: name, slug, lat/lng, corridor ENUM (kubwa_cbd, nyanya_idu, lugbe_cbd, garki_wuse), union_id (NURTW branch), photo_path placeholder, avg_wait_time_mins 10-45 (peak morning), is_major_hub bool (true for Nyanya, Berger, Kubwa Junction, Lugbe, Zuba), passenger_volume_daily INT (500-5000 from above), state (FCT/Nasarawa/Niger)

### 3. Users (100) — `UserSeeder`
- 1 Admin: admin@workride.ng / admin1234, role admin, verification_level 3
- 5 Workplace Admins: wuse.admin@workride.ng etc, role workplace_admin, workplace_id linked
- 30 Drivers: driver1..30@workride.ng, role driver, verification_level 3, phone 0803XXXX, has vehicle
- 40 Passengers: passenger1..40@workride.ng, role passenger, verification_level 2 (NIN verified)
- 15 Both (drive & ride): both1..15@workride.ng, role both, verification_level 3
- 10 Volunteers: volunteer1..10@workride.ng, role volunteer, verification_level 2
- All users: fake names (Faker Nigerian names), avatar placeholder, nin_hash = sha256('22345678901'), nin_last4 = last4, is_banned false, has_overdue_ride_credit false for 90%
- 10% with has_overdue_ride_credit true for testing block

### 4. Verifications (150) — `VerificationSeeder`
- For each user: workplace verification (type=workplace_id, status=approved, document_hash=sha256(staff_id), tier=1, liveness_score 80-95)
- For Level 2 users: NIN verification (type=nin, tier=2, provider=identitypass, liveness_score 75-90, face_embedding_hash fake, selfie_encrypted_path fake)
- For Drivers: driver license (type=driver_license, tier=3, provider=smile, liveness_score 85-98, anti_spoofing_score 80-95)
- Add 20 verification_attempts with mixed scores for admin view (some failed liveness)

### 5. Vehicles (40) — `VehicleSeeder`
- Link to drivers/both users
- Make: Toyota Corolla, Camry, Sienna, Hiace, Coaster
- Plate: Abuja format ABJ-123XY
- Seats: 4 for sedan, 14 for Hiace, 30 for Coaster
- Type: sedan, coaster, staff_bus, danfo
- Year 2015-2023, is_verified true for 80%

### 6. Trips (80) — `TripSeeder` — MOST IMPORTANT FOR LIVE DEMO — Must use real corridors
- Corridors: 20 kubwa_cbd (Kubwa, Zuba, Suleja → Berger → CBD), 20 nyanya_idu (Nyanya, Mararaba, Masaka, Karu, Jikwoyi → AYA → Idu), 20 lugbe_cbd (Lugbe, Gwagwalada, Kuje, Airport Road → Garki), 20 garki_wuse (City loop: Wuse Market → Area 1 → Apo)
- Status distribution: 25 active (live now, current_lat/lng near junctions, departure_time within -30min to +2h), 25 scheduled (future today/tomorrow), 20 completed (past 7 days), 10 cancelled
- For active trips: current_lat/lng must be interpolated along real route (e.g., active trip from Zuba → Berger should have current position near Dutse or Kubwa), speed 20-60 km/h, total_seats 4/14/30, available_seats = total - booked, fare_per_seat 600-800 (Kubwa-CBD 800, Nyanya-CBD 600, Lugbe-CBD 700, Zuba-CBD 1000), is_free_volunteer true for 5 volunteer trips, departure_time now +/- 1h
- Route name examples MUST use real locations: "Zuba → Kubwa → Berger → Secretariat", "Nyanya Under-Bridge → AYA → Wuse Market", "Suleja → Madalla → Kubwa Junction → Berger", "Gwagwalada → Lugbe → Area 1 → Federal Secretariat", "Mararaba → Nyanya → AYA → Central Business District", "Kuje → Lugbe Across → Garki Area 11"
- Driver: link to driver user, ensure driver lives near origin (e.g., driver from Suleja drives Suleja-CBD)
- Use TripFactory with states: active, scheduled, completed, include corridor-specific fare

### 7. TripWaypoints (200) — `TripWaypointSeeder`
- For each trip: 4-6 waypoints: origin junction, 2 intermediate (e.g., Banex, Wuse Market), destination workplace
- Sequence 1..n, label from junctions, lat/lng from junctions
- This builds shapes.txt for GTFS

### 8. Bookings (150) — `BookingSeeder`
- Link trips to passengers
- Status: 30 requested, 50 confirmed, 20 boarded, 30 completed, 10 no_show, 10 cancelled
- For active trips: 2-4 bookings per trip, pickup_lat/lng = junction lat/lng + small random offset
- fare_paid 600-800, payment_method: wallet (40%), cash (20%), subsidy_credit (20%), ride_credit (10%), free_volunteer (10%)
- For completed trips: create transactions

### 9. Wallets + Transactions (100 wallets, 300 transactions) — `WalletSeeder`
- Each user has wallet: cash_balance 0-20000, subsidy_credits 0-15000, earned_balance 0-10000, cash_collected_log, version 1
- Transactions: reference unique BOOK-{id}-HOLD etc, type credit/debit/subsidy/refund/hold/capture/earned/fee/p2p_debit/p2p_credit, amount, meta json (trip_id, booking_id), idempotent
- For completed bookings: hold → capture flow
- For driver earnings: earned transactions

### 10. RideCredits (30) — `RideCreditSeeder`
- 20 owed, 5 repaid, 5 overdue
- user_id = passenger who booked with ride_credit
- trip_id = trip taken
- seats_owed 1-2, seats_repaid 0-1, fare_value 600-1200, due_date now()+/- days, status
- Link to trips where driver repaid

### 11. P2P Transfers (40) — `P2pTransferSeeder`
- sender = driver with earned_balance, receiver = passenger/both
- amount 500-5000, type cash/earned, reference unique P2P-{sender}-{time}, status completed for 35, pending 5
- Create corresponding transactions p2p_debit/p2p_credit

### 12. Payouts (20) — `PayoutSeeder`
- wallet_id, amount 2000-15000, status completed/pending, reference PAYOUT-...

### 13. RoadEvents + RoadSegments (100 events, 20 segments) — `RoadEventSeeder`
- road_events: lat/lng along Kubwa-CBD route, type pothole/speed_bump/rough/flood, severity low/medium/high, speed 20-60, accelerometer_z 10-25, user_id (driver), created_at within last 30 days
- Cluster 5 events within 20m to make confirmed potholes (for testing clustering)
- road_segments: road_name "Kubwa Expressway", avg_iri 3-12, condition excellent/good/fair/poor, last_updated now

### 14. Demand Surveys (100) — `DemandSurveySeeder`
- junction_id FK, count 5-40 people waiting, destination_text "CBD", "Secretariat", hour 6-20, day_type weekday/weekend, collected_by user_id (intern), lat/lng junction, photo_path placeholder

### 15. DemandRequests (50) — `DemandRequestSeeder`
- user_id, pickup_lat/lng junction, destination_text, passengers_count 1-4, requested_at within last 2h, status pending/matched

### 16. GTFS Stops + Routes (from junctions) — `GtfsSeeder`
- gtfs_stops: stop_id from junctions, stop_name, stop_lat/lng
- gtfs_routes: 4 routes KUB-CBD, NYA-IDU, LUG-CBD, GAR-WUS

### 17. ChatMessages (100) — `ChatMessageSeeder`
- trip_id, sender_id, message "I dey Berger junction", created_at within trip time

### 18. ImpactStats (100 users) — `ImpactStatSeeder`
- user_id, total_trips 5-50, co2_saved_kg, fuel_saved_litres, trees_equivalent

### 19. ActivityLogs (200) — `ActivityLogSeeder`
- user_id, action "trip.published", "booking.confirmed", "wallet.credited", meta json

### 20. ApiCostLogs (50) — `ApiCostLogSeeder`
- provider identitypass/smile, purpose nin_check/driver_liveness, cost_ngn 100, cost_usd 0.05, user_id, reference

---

## HOW TO IMPLEMENT:

### Files to Create:

- `database/factories/` — Update all factories: UserFactory (role, verification_level, nin_hash), WorkplaceFactory, VehicleFactory, TripFactory (with states active/scheduled/completed/cancelled, with current_lat/lng), BookingFactory, WalletFactory, RoadEventFactory, JunctionFactory, DemandSurveyFactory, RideCreditFactory, P2pTransferFactory

- `database/seeders/DatabaseSeeder.php` — Call in order:
```
$this->call([
  WorkplaceSeeder::class,
  JunctionSeeder::class,
  UserSeeder::class,
  VerificationSeeder::class,
  VehicleSeeder::class,
  TripSeeder::class,
  TripWaypointSeeder::class,
  WalletSeeder::class,
  BookingSeeder::class,
  RideCreditSeeder::class,
  P2pTransferSeeder::class,
  PayoutSeeder::class,
  RoadEventSeeder::class,
  DemandSurveySeeder::class,
  DemandRequestSeeder::class,
  GtfsSeeder::class,
  ChatMessageSeeder::class,
  ImpactStatSeeder::class,
  ActivityLogSeeder::class,
  ApiCostLogSeeder::class,
]);
```

- Use `WithoutModelEvents` trait to avoid triggering jobs during seeding
- Use `DB::transaction` for wallet seeding to handle version optimistic locking (set version=1)
- Use Faker `fake()->randomElement()`, `fake()->latitude(8.9,9.2)`, `fake()->longitude(7.3,7.6)` for Abuja bounds

### Key Logic:

- For active trips: `current_lat` = interpolate between waypoints based on departure_time, so map shows moving vehicles
- For wallet: After creating wallet, set version=1 manually (fix null bug from DEVELOPMENT-LOG.md)
- For transactions: Generate reference with `Str::uuid()` + prefix for idempotency
- For bookings with ride_credit: Also create ride_credit record
- For driver earnings: After booking completed, call `WalletService::creditEarned` logic but directly in seeder for speed

### Testing After Seeding:

- `php artisan migrate:fresh --seed` must complete <30 seconds
- `php artisan gtfs:generate` must generate valid zip with stops from junctions
- `/admin` must show: 20 live trips, 100 users, wallet balances, road heatmap with 20 red potholes, demand survey chart
- `/trips` must show live map with moving vehicles (use current_lat/lng)
- `/wallet` for passenger1 must show cash + subsidy + earned balances + transactions + P2P history
- Login as driver1@workride.ng / password → see 2 active trips, earnings

### Command:

Add `php artisan workride:seed-demo` that calls `db:seed --class=DatabaseSeeder` + `gtfs:generate`

---

## DELIVERABLES:

- All factories updated with Abuja realistic data + states
- 20+ seeder classes
- DatabaseSeeder ordered correctly
- `php artisan migrate:fresh --seed` works first time, no foreign key errors
- Demo data enough to test every function without manual creation
- README in `database/seeders/README.md` explaining how to test each feature with seeded data (which user to login)

Build this — this is your investor demo data. When investor logs in, they must see live vehicles moving on map, not empty screen.

