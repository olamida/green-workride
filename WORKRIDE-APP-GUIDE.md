# Green WorkRide — Application Development Guide v3.0
### Community-Focused, Subsidy-Enabled, Operations-Ready, Globally Scalable Transit Intelligence Platform

> **Version:** 4.0 (Demand Research Edition - Includes BRT Pre-Design Survey, Junction Counts, OD Matrix, Probe Data + All Ops)
> **Previous:** v2.0 Funding-Ready | v1.0 MVP
> **Architecture:** Dual-App System - 1) Rider PWA 2) Ops Control Tower
> **Platform:** Laravel 11 + Tailwind + MySQL 8 + Redis + Reverb + GTFS + RoadLab Sensors
> **Tagline:** *"Built by amateurs, for the working class. From Abuja to the world."*
> **Business Structure:** Community Interest Company (CIC) Hybrid — 60% Community Trust, 40% For-Profit Operating Co.

---

## Table of Contents
1. [Executive Summary & Crisis Fit](#1-executive-summary)
2. [Business Model & Funding Strategy](#2-business-model)
3. [Dual-App Architecture: Rider + Ops Control Tower](#3-application-architecture)
4. [Database Schema - 45 Tables (Ops Included)](#4-database-schema)
5. [Module Reference - 12 Modules](#5-module-reference)
6. [Workflows & Processes - 10 Workflows](#6-workflows)
7. [User Guide by Role - 6 Roles](#7-user-guide-by-role)
8. [Admin & Ops Control Tower Guide](#8-admin-guide)
9. [Demand Forecasting & Event Planning](#9-demand-forecasting)
10. [Stakeholder Management & Union Integration](#10-stakeholder)
11. [Fleet Lifecycle: Acquisition, Maintenance, Disposal](#11-fleet)
12. [GTFS & Google Transit Integration](#12-gtfs)
13. [Road Sensor & Intelligence Module](#13-road-sensor)
14. [Wallet, Subsidy & Receipts](#14-wallet)
15. [Regulatory, Legal & Competition Strategy](#15-regulatory)
16. [International Adoption & Scaling](#16-international)
17. [PWA & Award-Winning UI System](#17-pwa)
18. [Build Sprints - Operations First](#18-sprints)
19. [Git & Version Control - Commit Every Phase](#19-git)


---

## 1. Executive Summary

**Problem (Nigeria 2026):** Fuel N1,200-1,450/litre (505% increase from N175 in 2023), transport fares up 50-100% (Kubwa-Bannex N500→N1,000), workers spend 50% of salary on commuting (was 20%), FCT residents abandon cars, walk long distances, low-income earners stranded. No standard public transport.

**Solution:** WorkRide is NOT ride-hailing. It is **3 layers:**
1. **Ride-Share & Staff Bus Aggregator** for verified civil servants (corridor-based, fixed price, volunteer free rides)
2. **GTFS Publisher** — Abuja's first transit feed, searchable on Google Maps (like Hyderabad JNTUH→Charminar experience)
3. **Road Intelligence Network** — 1,000 cars as probes using phone sensors (accelerometer, GPS, gyroscope) to map potholes, IRI, slope — World Bank RoadLab method

**Inspiration:** Nairobi Digital Matatus (135 routes mapped → Google Maps), India Quick Ride (37k Wipro staff saved Rs 3.8 Crore fuel), Lagos BRT ($1.7M/km vs $6M global, 180k riders/day), Brazil BlaBlaCar (14M trips).

---

## 2. Business Model & Funding Strategy (NEW - Comprehensive)

### 2.1 Hybrid Structure for Community + Profit
To get funding as community project AND be sustainable like Uber:

**WorkRide Community Trust (60% - Non-profit):**
* Owns: GTFS data, Road Intelligence data, Open-source core
* Mission: Subsidy for working class, research, open data for FERMA
* Funded by: Grants, Govt, International donors

**WorkRide Operations Ltd (40% - For-Profit):**
* Owns: App, Wallet, Matching algorithm, Premium features
* Mission: Profit to sustain workforce, tech, expansion
* Revenue shares 15% of profit back to Trust

This is how **Mozilla + Firefox**, **OpenStreetMap** work — fundable as social impact, but sustainable.

### 2.2 Revenue Streams (8 Streams - Data Driven)

| # | Stream | How It Works | Pricing (Abuja) | Projected Monthly (at 2,000 daily rides) | Global Example |
|---|--------|--------------|-----------------|----------------------------------------|----------------|
| 1 | **Commission on Paid Rides** | 10% on paid carpool (vs Bolt 20-25%) | ₦600 fare → ₦60 commission | ₦2.6M | Quick Ride takes 10% |
| 2 | **Corporate Corridor Pass (B2B)** | Ministry/Company pays per staff/month for unlimited rides in corridor | ₦15,000/staff/month (saves staff ₦30k) | ₦7.5M for 500 staff (1 MDA) | Wipro/Infosys model - 6 of top 10 IT firms |
| 3 | **Staff Bus Seat Monetization** | List empty seats of FCTA/Private staff buses on WorkRide | ₦200/seat, 50% to bus owner | ₦1.2M (20 buses x 30 seats x 50% full) | Lagos BRT cooperative model |
| 4 | **Subsidy Management Fee** | MDA loads subsidy_credits for staff, we charge 3% fee for audit trail | ₦10M subsidy → ₦300k fee | ₦900k (3 MDAs) | India fuel subsidy token programs |
| 5 | **Road Intelligence API** | Sell road condition data: IRI, potholes CSV, traffic | ₦500k/month to logistics, FERMA, construction | ₦1.5M (3 clients) | World Bank RoadLab sells to govts |
| 6 | **GTFS Data License** | Charge Google, Moovit, private apps for premium GTFS-RT | Free static GTFS (community), Paid RT at $0.01/call | ₦800k | Nairobi Digital Matatus licensed to 5 apps |
| 7 | **Green Credits & CSR** | Companies buy CO2 certificates: 1 tonne = ₦5k for ESG reporting | Wipro saved 1,308 tonnes | ₦2M (400 tonnes) | Capgemini 65% emission reduction campaign |
| 8 | **Premium Verification + Insurance** | ₦1,000/year for priority matching, accident micro-insurance | ₦1k/year | ₦200k (200 drivers) | GrabShare premium |

**Total MVP:** ₦16.7M/month at just 2,000 rides/day + 1 MDA + 3 data clients. Break-even at 800 rides/day.

### 2.3 Funding Ladder (From Community to Global)

**Stage 1: Community & Grants (Now - $10k-$50k)**
* Target: **FCT Innovation Fund, Ford Foundation, World Bank Youth Innovation, Google.org, MIT Solve**
* Pitch: "Abuja's first GTFS + RoadLab, built by civil servants, open data for FERMA, reduces fuel poverty"
* Deliverable: GTFS feed + 500 users + Road map
* Equity: 0% - grant

**Stage 2: Government & MDA Subsidy (Month 3-6 - $50k-$200k)**
* Target: **Head of Service, Federal Ministry of Works, FCTA Transport Secretariat, Office of the Vice President (palliatives)**
* Pitch: "Your N10M palliative in subsidy_credits → trackable, auditable, goes directly to worker transport, not cash diversion. Dashboard shows every ride, CO2 saved."
* Model: MDA buys subsidy_credits at 10% discount, distributes to staff via WorkRide wallet
* Regulation: Get letter as "Approved Staff Mobility Partner for FCT"

**Stage 3: Angel & Impact Investors (Month 6-12 - $200k-$500k)**
* Target: **Lagos Angel Network, Future Africa, Ventures Platform, Google for Startups Africa**
* Valuation: $1.5M pre-money (based on GTFS data moat + 2,000 daily active + MDA contract)
* Use: Hire 5 engineers, launch 3 corridors, OSRM self-hosted maps

**Stage 4: VC & International Expansion (Year 2 - $2M-$5M)**
* Target: **IFC (invested $15M in BlaBlaCar Brazil), Partech, YC**
* Metrics needed: 10k DAU, 100k rides/month, $50k MRR, GTFS in 2 cities (Abuja + Lagos)
* Expansion: Nairobi (copy Digital Matatus), Accra, Kampala — same fuel crisis, same playbook

**Stage 5: Global Platform (Year 3+ - Like Uber)**
* Become **GTFS-Flex standard for informal carpools** globally
* White-label to governments: "WorkRide for Nairobi", "WorkRide for Manila"
* IPO path: Community Trust retains data ownership, Operations Ltd lists

### 2.4 Profit Allocation (Sustainable Workforce)
* 40% - Workforce salaries + tech (engineers, verification officers)
* 20% - Research (RoadLab AI, IRI accuracy, EV transition)
* 15% - Community Trust (subsidy for low-income, free rides fund)
* 15% - Profit reserve + expansion
* 10% - Competition & Marketing (referral bonuses, Green Points)

### 2.5 Research & Competition Fund (Built-in)
* **WorkRide Research Lab:** Partner with University of Abuja, FUTA — publish papers on IRI in Nigeria, fuel saving models (like MIT did)
* **Annual WorkRide Green Challenge:** Prize ₦1M for best corridor optimization idea — builds community, PR
* **Bug Bounty:** ₦10k for pothole reporting, ₦50k for security bug

---

## 3. Application Architecture

### Philosophy: DUAL-APP SYSTEM
**App 1: WorkRide Rider PWA (Public)** - Lightweight <1MB for passengers/drivers to book. Built with Blade+Tailwind+Alpine.
**App 2: WorkRide Control Tower (Ops) - Filament Admin PWA** - The brain for ops team: Demand Calendar, Duty Roster, Asset & Maintenance, Driver Scores, Finance Settlement, Stakeholder Remittance, GTFS Dashboard, Road Heatmap. This split is how Uber/Lyft/Bolt operate (Rider/Driver/God-view).

Core: Modular Monolith → Event-Driven → GTFS Publisher → Road Intelligence → Operations. Change Control: Identify (sensor), Ensure beneficial (verified), Contain (corridor + geofence), Manage (audit log + maintenance + stakeholder ledger).

### Directory Structure
```
workride/
├── app/
│   ├── Enums/ (10 enums: UserRole, VerificationLevel 0-3, TripStatus, BookingStatus, Corridor kubwa_cbd/nyanya_idu/lugbe_cbd, PaymentMethod wallet/cash/subsidy_credit/free_volunteer, TransactionType, RoadEventType, RoadCondition, VehicleType)
│   ├── Http/Controllers/
│   │   ├── Api/V1/ (Auth, Verification, Trip, Booking atomic, Wallet dual balance with optimistic locking version, Chat Reverb, Gtfs generate zip + GTFS-RT protobuf, RoadSensor POST road-events, Impact CO2, Workplace)
│   │   └── Web/ (Home, Dashboard, TripBoard Linear-style)
│   ├── Models/ (22: User, Workplace with lat/lng + geofence, Verification document_hash never raw NIN, Vehicle, Trip with is_free_volunteer bool indexed + corridor + current_lat/lng + departure_time, TripWaypoint, Booking pickup_lat/lng + fare_paid + payment_method + unique trip_id+passenger_id, Wallet cash_balance + subsidy_credits + version, Transaction reference unique idempotent, ChatMessage, ImpactStat co2 + fuel_saved, RoadEvent lat/lng + type pothole/bump/rough + severity + accelerometer_z, RoadSegment avg_iri + condition excellent/good/fair/poor, GtfsStop/Route, ActivityLog)
│   ├── Services/ (VerificationService hash, TripMatchingService Haversine 2km + corridor + time window, PricingService fixed-price anti-surge volunteer=0, WalletService hold/capture/refund subsidy first, GeofenceService 500m triggers + FCT polygon, Co2Service (occupants-1)*distance*0.12kg tree=co2/21, GtfsService generates 7 files agency/stops/routes/trips/stop_times/calendar/shapes → zip, RoadIntelligenceService IRI calc + pothole clustering 5 reports within 20m=confirmed, NotificationService)
│   ├── Events/Listeners (TripPublished→NotifyNearby, BookingConfirmed→UpdateSeats+HolFare, UserArrivedAtPickup→FCM, RoadPotholeConfirmed→Alert FERMA)
│   └── Jobs (ProcessNinVerification, CalculateImpactJob, GenerateGtfsFeedJob nightly, CalculateIRIJob)
├── database/migrations (28), seeders (WorkplaceSeeder 45 FCT MDAs, CorridorSeeder, GtfsStopSeeder 50 stops)
├── resources/views (layouts/app Blade Tailwind Alpine 8px grid Inter+Sora, auth, trips/board with Corridor Chips + Live View card 5-sec clip, wallet, gtfs/dashboard, road/map Green/Yellow/Red IRI heatmap Leaflet OSM, pwa/manifest)
├── resources/js (app.js Alpine, useGeolocation, useRoadSensor devicemotion Z>15=pothole, pwa/service-worker)
├── routes (api.php V1 sanctum, web.php, channels.php Reverb)
└── config/workride.php (CO2 factors, max fare per corridor, geofence radius 500m, IRI thresholds)
```

### Tech Stack
Laravel 11, PHP 8.3, MySQL 8 Spatial, Redis GEO+Queue, Reverb WebSocket, Blade+Tailwind+Alpine (<50kb), OSM+OSRM self-hosted 90% cost save, Paystack/Moniepoint, GTFS+GTFS-RT, PWA

---

## 4. Database Schema

| Table | Key Columns | Indexes | Business Rule |
|-------|-------------|---------|---------------|
| users | id, name, email, phone unique, workplace_id FK, role passenger/driver/both/volunteer, verification_level 0-3, avatar, is_banned | phone, workplace_id, verification_level | Level1+ to book, Level3 to drive paid |
| workplaces | id, name, zone CBD/Idu/Garki, lat,lng, geofence_radius_m 500, is_government bool | zone, spatial lat/lng | Seed 45 FCT MDAs |
| verifications | id, user_id FK, type workplace_id/nin/drivers_license/vehicle, document_hash SHA256, status, verified_by | user_id+type unique | Never store raw NIN |
| vehicles | id, user_id FK, plate_number unique, make, model, color, seats, type sedan/coaster/staff_bus/danfo, papers_verified bool | user_id | — |
| trips | id, driver_id FK, vehicle_id FK, route_name, corridor ENUM, origin/destination_text, current_lat/lng, total_seats, available_seats, fare_per_seat 0 for volunteer, is_free_volunteer bool indexed, status scheduled/active/completed/cancelled, departure_time indexed, waypoints json | corridor+departure_time+status composite, spatial current, is_free_volunteer | Fare max ₦800 anti-surge |
| trip_waypoints | id, trip_id FK, label Berger Junction, lat/lng, sequence | trip_id+sequence | — |
| bookings | id, trip_id FK, passenger_id FK, pickup_lat/lng, status requested/confirmed/boarded/completed/no_show/cancelled, fare_paid, payment_method wallet/cash/subsidy_credit/free | trip_id+passenger_id unique, passenger_id | Atomic decrement |
| wallets | id, user_id FK unique, cash_balance, subsidy_credits, cash_collected_log, version INT optimistic locking | user_id | Subsidy first |
| transactions | id, wallet_id FK, type credit/debit/subsidy/refund/hold/capture, amount, reference unique idempotent, meta json | reference unique, wallet_id | Idempotent |
| chat_messages | id, trip_id FK, sender_id FK, message | trip_id+created_at | Per-trip |
| impact_stats | id, user_id FK unique, total_trips, co2_saved_kg, fuel_saved_litres, trees_equivalent, level | user_id | — |
| road_events | id, user_id FK, lat/lng, type pothole/bump/rough/flood, severity 1-5, speed, accelerometer_z, is_confirmed bool | spatial lat/lng, type | 5 reports within 20m=confirmed |
| road_segments | id, road_name Nyanya-Keffi, start/end lat/lng, avg_iri, condition excellent/good/fair/poor, last_updated | road_name | World Bank RoadLab Excellent<4 Good4-6 Fair6-10 Poor>10 |
| activity_logs | id, user_id FK, action, model_type, model_id, changes json | model | Change Control |

---

## 5. Module Reference

### A. Identity & Gatekeeping
AuthController (Google Sign-In, email), VerificationController (NIN hash SHA256 only last 4 + hash), VerificationService, Middleware EnsureVerifiedWorker Level1+ book, EnsureDriverVerified Level3 paid Level1 volunteer free. Flow: Sign-up → Workplace ID upload → Admin approve L1 → NIN hash L2 → Driver docs L3.

### B. Trip & Matching
TripController publishTrip validates inside FCT polygon via GeofenceService, searchTrips corridor+2km Haversine, updateLocation 15s when active, completeTrip. TripMatchingService findMatches($from,$to,$lat,$lng,window=30) orders distance+departure_time. PricingService fixed per corridor Kubwa-CBD max ₦800 volunteer=0 anti-surge.

### C. Wallet & Subsidy
Dual balance cash_balance + subsidy_credits. WalletService hold on booking, capture on boarded, refund on cancel, optimistic locking WHERE version=$version prevents double spend. Idempotency reference BOOK-{bookingId}-HOLD. Subsidy first debit. MDA can bulk credit via CSV.

### D. GTFS & Transit Intelligence
GtfsService reads trips+waypoints → generates agency.txt stops.txt routes.txt trips.txt stop_times.txt calendar.txt shapes.txt → zips to /public/gtfs/gtfs.zip. GtfsController serves GTFS-RT protobuf /gtfs-rt/vehicle_positions.pb live lat/lng. Job GenerateGtfsFeedJob nightly + on publish.

### E. Road Sensor
RoadSensorController POST /road-events from PWA useRoadSensor.js only when trip active. RoadIntelligenceService pothole clustering 5 reports within 20m 72h=confirmed, IRI calc World Bank RoadLab formula IRI=α*RMS(acc_z)/speed+β maps Excellent<4 Good4-6 Fair6-10 Poor>10. Map /road/map Green/Yellow/Red Leaflet OSM.

### F. Impact Analytics
Co2Service saving=(occupants-1)*distance_km*0.12kg tree=co2/21 fuel_saved=distance/10*occupants (approx). Leaderboard per workplace.

---

## 6. Workflows

### Workflow 1: Publish→Book→Ride→Complete (Core)
Driver L3 publish Trip Kubwa→Secretariat total_seats4 departure06:45 free_volunteer false fare600 waypoints [Kubwa Junction, Berger] → GeofenceMiddleware checks inside FCT → Event TripPublished → notifies nearby via Reverb+FCM → Passenger L1+ search Kubwa to Secretariat sees 3 options Volunteer Free+2 Paid → Booking atomic DB::transaction lock FOR UPDATE decrement available_seats create booking holdFare subsidy first → Live driver loc 15s → enters 500m waypoint → Event UserArrivedAtPickup FCM "500m away" → Boarded capture → Completed CalculateImpactJob updates ImpactStat + road_events aggregate

### Workflow 2: Volunteer Free (Supply Bootstrap for Fuel Crisis)
Driver L1+ toggle is_free_volunteer=true fare0 → passenger books free → driver earns 10 Green Points + fuel discount coupon → builds trust when fuel N1,400

### Workflow 3: Corporate Subsidy (MDA Palliative)
MDA admin → /admin/wallets/bulk-credit → uploads CSV staff_id,credits → WalletService credits subsidy_credits with reference MDA-2026-001 → staff books using subsidy → transaction meta MDA audit → MDA sees dashboard trips, CO2, fuel saved → monthly report printable

### Workflow 4: Road Intelligence Loop
Phone Z>15 → POST /road-events → RoadIntelligenceService clusters if 5 reports confirmed pothole → activity_log → shows /road/map Red dot → Admin export CSV for FERMA: road_name,lat,lng,severity,reports_count

### Workflow 5: GTFS to Google
Nightly Job generates gtfs.zip → Admin validates feedvalidator.mobilitydata.org → Upload to transitpartnerprogram.withgoogle.com → After approval WorkRide searchable on Google Maps as "WorkRide Staff Bus 2 - 6:45AM"

---

## 7. User Guide by Role

| Role | Can Do | Cannot Do | Funding Relevance |
|------|--------|-----------|-------------------|
| Passenger L1+ | Search corridor, book seat, chat, wallet cash+subsidy, impact, report road event, GTFS planner | Publish paid | Subsidy beneficiary |
| Driver L3 | All passenger + publish paid/free, live loc, cash_collected_log, withdraw earnings | Drive if banned | Earns + saves fuel |
| Volunteer L1 | Publish ONLY free rides is_free_volunteer=true earn Green Points | Set fare>0 | Bootstraps supply, CSR |
| Workplace Admin MDA | View staff usage, top-up subsidy_credits, view CO2 report, export | Verify NIN | Pays for Corridor Pass |
| Admin Super | Verify IDs, manage workplaces/corridors, all trips/bookings, wallets credit subsidy, road map, generate GTFS, export reports, ban users, backup, research dashboard | — | Manages Trust + Operations |

---

## 8. Admin & Governance Guide

### Dashboard /admin
Stats: trips today, active drivers, bookings, fuel saved litres, CO2 saved kg, confirmed potholes, GTFS last generated, MRR, subsidy_credits issued. Charts: trips per corridor, revenue per day, road condition pie, subsidy utilization.

### Management
Verifications queue pending staff ID/NIN hash/driver docs Approve/Reject → notifies. Trips list filters corridor/status/free/paid view map cancel refund. Workplaces CRUD name/zone/lat/lng/geofence_radius seed 45 MDAs. Wallets view all manually credit subsidy_credits Export transactions. Road Intelligence map filters date/severity/confirmed list Export CSV for FERMA. GTFS Dashboard validate download gtfs.zip view GTFS-RT status last Google submission. Users ban/unban change role reset password view activity log. Settings config/workride.php via UI max fare per corridor geofence radius IRI thresholds CO2 factor commission 10%.

### Governance (Community Trust)
Board: 3 community (drivers, passengers, MDA rep) + 2 tech + 1 legal. Quarterly audit: subsidy usage, road data open, profit share 15% to Trust. Annual Green Challenge prize ₦1M.

### Backup
/admin/backup → SQL dump + GTFS zip + road_events CSV

---

## 9. GTFS & Google Transit Integration

Why Abuja has no GTFS first publisher wins. Files: agency.txt agency_id agency_name WorkRide agency_url, stops.txt stop_id stop_name Berger Junction stop_lat lon, routes.txt route_id agency_id route_short_name KUB-CBD route_type 3 bus, trips.txt trip_id route_id service_id, stop_times.txt trip_id arrival departure stop_id sequence, calendar.txt service_id monday-sunday start end, shapes.txt shape_id pt lat/lon/seq from waypoints.

GTFS-RT: /gtfs-rt/vehicle_positions.pb + /gtfs-rt/trip_updates.pb protobuf from active trips current_lat/lng Google polls 30 sec.

Submission: Host at https://workride.ng/gtfs/gtfs.zip → Apply transitpartnerprogram.withgoogle.com → Validate feedvalidator.mobilitydata.org

---

## 10. Road Sensor & Intelligence Module

Sensors: GPS+Speed route traffic slope, Accelerometer Z pothole Z>15, Gyroscope tilt swerving, Barometer hill grade.

Collection Rules: ONLY when trip status=active dynamic geofencing privacy + battery, Sample 10Hz batch 30 sec, Anonymized road_events does NOT store driver name public map only lat/lng severity.

IRI: Excellent <4 Good 4-6 Fair 6-10 Poor >10 World Bank RoadLab.

Use Cases Nigeria: Route optimization avoid Poor save 30% fuel, FERMA Report export confirmed potholes monthly, Driver rating penalize hard pothole hits.

---

## 11. Wallet, Subsidy & Receipts

Wallet dual cash_balance via Paystack + subsidy_credits MDA funded Flow Booking Hold Boarded Capture Cancel Refund No-show Capture 50% Optimistic Locking version prevents race.

Receipts 8 types printable QR trip_id+reference dompdf:
1 Trip Booking Receipt passenger
2 Driver Earnings Receipt
3 Wallet Top-up Receipt
4 Subsidy Credit Receipt MDA audit
5 Fuel Saved Certificate CSR
6 CO2 Impact Certificate
7 Road Event Report FERMA
8 Monthly Commute Statement salary deduction proof

---

## 12. Regulatory, Legal & Competition Strategy

### Government Regulations (Nigeria)
* **NIN & NDPR:** Never store raw NIN, only SHA256 hash + last 4. Get NDPA compliance, consent checkbox. Data hosted in Nigeria (MainOne/NG-COM).
* **FCTA & VIO:** Register as "Staff Mobility Cooperative" not ride-hailing to avoid VIO crackdown. Get letter as Approved Partner. Vehicles must have papers_verified.
* **NITDA:** Open data license for GTFS static (CC BY 4.0), private for RT. Road data shared with FERMA free.
* **Insurance:** Partner with Leadway/Coron for micro-insurance ₦100/trip included in fare.
* **Tax:** VAT on commission only, not on subsidy_credits (palliative). Get TIN.

### Competition (Bolt, Uber, InDrive)
* **Differentiation:** Bolt is surge + individual + expensive. WorkRide is fixed-price corridor + verified colleagues + subsidy + Google Maps searchable + road data. Can't be copied easily.
* **Moat:** GTFS data + workplace verification + MDA contracts + road intelligence. Bolt cannot get MDA staff ID list.
* **Anti-Competition:** Keep commission 10% vs Bolt 20-25%. Volunteer free rides kill price war. Community Trust prevents acquisition.
* **Union Strategy:** Don't fight NURTW. Onboard danfo/coaster drivers as verified drivers, give them cooperative shares like Lagos BRT did (First BRT Cooperative). 1 high-capacity bus replaces 5 danfos.

### International Adoption Playbook
* **Phase 1 Abuja:** Prove 2,000 rides/day + GTFS + 1 MDA.
* **Phase 2 Lagos:** Copy BRT low-cost model $1.7M/km vs $6M. Partner LAMATA, seed 75k danfo routes.
* **Phase 3 Africa:** Nairobi (Digital Matatus already mapped, need carpool layer), Accra, Kampala — same fuel crisis. White-label GTFS-Flex.
* **Phase 4 Global South:** Manila, Haiphong, Zhengzhou World Bank pilots — pitch as "WorkRide for Manila".
* **Tech for Global:** Multi-tenant Laravel: `workplaces` table has `country_id`, `city_id`. GTFS per city. OSM self-hosted per region. Currency via config.

### Risk Mitigation
* Fuel price drops: Pivot to Green Credits + time saving pitch
* Govt bans: Community Trust owns data, can operate as NGO
* Fraud: NIN hash + staff ID + guarantor + rating + activity_log
* Low smartphone: USSD fallback *347*WORK# for booking (future)

---

## 13. International Adoption & Scaling

Multi-tenant: countries table, cities table, workplaces belong to city. GTFS per city. OSRM per region. Currency config/workride.php. Language: English, Hausa, Yoruba, French for Africa.

Scaling: Phase1 Single VPS Hetzner, Phase2 Vapor + Redis GEO, Phase3 OSRM self-hosted, Phase4 Kubernetes + S3.

White-label: WorkRide Core (open) + WorkRide City Pack (paid) for new city — includes WorkplaceSeeder + GTFS validator.

Global pitch: "From Abuja to the world — built by amateurs, proven in fuel crisis, now powering staff mobility in Nairobi, Manila, Bogota."

---

## 14. PWA & Award-Winning UI System (Linear+Stripe+Apple for Abuja)

Design System: Colors Forest Green #2E7D32 Gold #FBC02D Slate #0F172A Paper #F6F9F6, Typography Sora headings Inter body JetBrains Mono numbers 2 fonts max, 8px grid, glassmorphism blur20 12% white soft shadows, Motion Framer style purposeful 200ms spring, Speed <2s load no shift Tailwind <50kb.

Components: Corridor Chip large pill 60px live pulse KUBWA→CBD •12 leaving •₦600 one tap, Live Trip Card driver avatar verification badge 5-sec looped road video seats fare IRI Green/Yellow CO2, Change Control Timeline vertical Booked→Driver 500m away NOW→Boarded→Completed 2.3kg CO2 saved auditable.

Future 2028: AR overlay potholes red gold optimal path, Voice "Find me free volunteer ride to Secretariat by 7:30" hands-free, Haptics Tick arriving Thud pothole ahead Buzz seat confirmed makes internet feel solution.

Landing: Above fold "Get to Federal Secretariat by 7:30am. Every day. Verified." + 3 Corridor Chips live count + Trust badges "Verified by 1,240 civil servants" "NIN-hashed not stored".

Routes 60+ Auth 8 /login /register /google/callback, Trips 12 /trips /api/v1/trips, Bookings 8 /bookings, Wallet 6 /wallet, Verification 5 /verify, GTFS 3 /gtfs /gtfs-rt, Road Sensor 2 /api/v1/road-events, Impact 2 /impact, Admin 30 /admin/*, PWA 2 /manifest.json /sw.js

---

## Appendix: Build Sprints (Funding Aligned)

Sprint1 Week1-2 Auth+Verification (NDPR compliant) → Demo to MDA for subsidy interest
Sprint2 Week3 Trip+Booking Atomic + Reverb chat → Demo to drivers, get 50 volunteer drivers
Sprint3 Week4 Wallet Dual + Paystack + Subsidy bulk credit → Demo to Ministry Finance for palliative tracking
Sprint4 Week5 GTFS Publisher → Submit to Google, pitch to World Bank as first Abuja GTFS
Sprint5 Week6 Road Sensor useRoadSensor.js + Heatmap → Pitch to FERMA, sell Road API
Sprint6 Week7 PWA Award UI + Impact Certificates → Launch Green Challenge, PR, apply to Google.org grant
Sprint7 Week8 Business Dashboard + Receipts + Export → Close Angel round with metrics

This v2.0 is funding-ready, regulation-ready, globally scalable.



---


---

## 9B. Real Demand Data Collection - BRT Pre-Design Method for Abuja (NEW - Field Research)

### The Problem: Abuja has no bus stops, people wait at junctions (Berger, Banex, Wuse Market). How to count them?

Lagos BRT did 3 studies before design:
1. Origin-Destination (OD) Survey - Where do people come from / go to?
2. Boarding-Alighting Counts - How many enter/exit at each stop?
3. Classified Traffic Counts - Vehicle types per road per hour

You will do same but with phones, not consultants.

### Method 1: Junction Count (Manual - ₦0, Day 1) - Like LAMATA 2008
Team of 3 NYSC interns with your Driver App in "Survey Mode":
- Stand at Kubwa Junction, Berger, Banex, Nyanya Under-Bridge, Lugbe
- Every 15 mins, count: How many people waiting? Destination they shout? Time? Take photo.
- App: POST /api/v1/demand-surveys {junction_id, count, destination_text, time, photo}
- Table: `demand_surveys` id, junction_id FK, count, destination, hour, day_type weekday/weekend, weather, collected_by, lat/lng, photo_path
- Result after 1 week: You know "Berger 6:30-7:30am = 320 people waiting, 80% to CBD"

### Method 2: Probe Data from Volunteer Cars (Automatic - Free)
Every WorkRide car is a sensor. When driver moves slowly at junction (speed <5km/h for >2 mins), log as "congestion + potential demand point". After 1 month, heatmap of where cars slow = where people wait.

Table: `probe_demand_points` lat/lng, avg_speed, dwell_time, times_visited

### Method 3: Workplace OD Survey (Google Form + App - ₦0)
Send to 500 civil servants in 3 MDAs: "Where do you live (Kubwa/Lugbe etc), What time do you leave home, What time you reach office, How much you pay?"
Result: OD Matrix: Kubwa→CBD 45%, Nyanya→CBD 30% etc.

Table: `od_surveys` id, workplace_id, home_area, departure_time, arrival_time, fare_paid, mode (keke/bus)

### Method 4: USSD/WhatsApp Demand Bot (For low-smartphone users)
*347*WORK# → "Where are you? Where you dey go? How many people dey there?" → Saves to demand_surveys. Critical for junction people without app.

### Method 5: Check-in Feature in Rider App (Crowdsourced)
Passenger at junction taps "I'm at Berger Junction, need ride to Secretariat, 2 people". Even if no driver yet, you collect demand. This becomes your future supply plan.

Table: `demand_requests` id, user_id, pickup_lat/lng, destination_text, passengers_count, requested_at, status (pending/matched)

### How This Becomes GTFS + Scheduling:
- demand_surveys → becomes GtfsStop importance + service frequency
- od_surveys → becomes routes.txt (KUB-CBD) + calendar.txt (peak hours)
- demand_requests → becomes real-time dispatch: Ops sees "12 people waiting at Nyanya now" → pushes extra bus

### Control Tower UI for This:
- Map: Circles sized by count (Berger big, Jabi small) per hour slider 5am-10pm
- OD Matrix Table: From/To counts
- Peak Hour Chart: 6am-9am surge curve per corridor
- Export for BRT report: CSV for World Bank format

This is exactly what World Bank and LAMATA did, but you do it with ₦50k interns + phones vs $100k consultants.



## 9. Demand Forecasting & Event Planning (NEW - Critical for Profit)

### Why 90% of apps fail: They react, not forecast.
Abuja demand is NOT random. It follows: Religious (Juma'a Fri 1-2:30pm low, Sunday Church 6-11am surge CBD), Government (FAAC, FEC Mondays, Salary week 25th-5th = +60% demand, Budget defence NASS), Events (Eagles at MKO Stadium, NYSC Kubwa), Weather (Rainy Apr-Oct +40% surge).

### Database: forecasts table
`id, date, event_type ENUM(church,mosque,govt,festive,weather,fuel_scarcity), event_name, corridor, expected_demand_multiplier decimal (0.7 to 2.0), recommended_extra_vehicles INT, notes, created_by`

### Feature in Ops Control Tower:
Demand Calendar UI: Admin inputs known events ahead. System auto-suggests: "This Friday is Juma'a + FAAC: Reduce CBD trips 30%, add 5 buses to mosque corridors after 2:30pm. Saturday is environmental sanitation - no movement 7-10am."

### Forecasting Algorithm (Phase1 Manual, Phase2 ML):
Phase1: Manual multiplier based on 3 months logging.
Phase2 Job `CalculateDemandForecastJob`: Uses past bookings per weekday/hour/corridor + events to predict. Formula: `predicted = avg_last_4_same_weekday * event_multiplier`

This saves 30% fuel by not deploying empty buses.

---

## 10. Stakeholder Management & Union Integration (NEW - Make or Break)

### Stakeholder Map Abuja:

| Stakeholder | Want | Strategy | Tech Feature |
|-------------|------|----------|--------------|
| NURTW/RTEAN/Jabi/Berger/Nyanya unions | Daily ticket, control | Make them agents: 5% of corridor bookings, their park = official hub. Don't fight. | `stakeholder_remittances` table: trip_id, union_id, amount, status |
| FCTA Transport Sec, VIO, DRTS | Regulation, safety, permit | Register as Staff Mobility Cooperative not commercial taxi. Get Approved Partner letter. | `permits` table: expiry reminders |
| MDAs (offices) | Punctuality proof for subsidy | Sell Subsidy Dashboard: trackable palliative | `subsidy_reports` printable |
| Security | Verification | NIN-hashed verification selling point | VerificationLevel 0-3 |

### Wallet Integration:
Every trip auto-calculates: `driver_earning = fare - commission(10%) - union_fee(5%) - insurance(₦100)`. Wallet has 3 payable balances: `driver_payable, union_payable, govt_tax_payable`. Daily settlement job via Moniepoint API.

---

## 11. Fleet Lifecycle: Acquisition, Maintenance, Disposal (NEW - Enterprise Grade)

### Philosophy: Start lean, asset-light.
Day1: Don't buy buses. Lease 3x 18-seater from existing companies (Agofure, Cross River) for Kubwa-CBD pilot. Capex 0. Use lease-to-own tracked in DB.

### Database Tables:

**assets:** `id, asset_type ENUM(bus,car,obd2_device), acquisition_type ENUM(lease,owned,donated), vin, plate_number unique, make, model, year, purchase_cost, lease_monthly, depreciation_rate, mileage INT, status ENUM(active,in_maintenance,grounded,disposed), assigned_driver_id, corridor, created_at`

**maintenance_schedules:** `id, asset_id FK, type ENUM(preventive_5000km,monthly_inspection), due_km, due_date, status`

**inspections (Daily Pre-Trip):** `id, asset_id FK, driver_id FK, date, tyre_photo_path, oil_level, interior_photo, is_passed bool, notes` - If not passed, trip publish blocked via middleware.

**faults:** `id, asset_id FK, reported_by, description, voice_note_path, severity, status ENUM(open,in_progress,fixed), created_at` - From driver app.

**telemetry (OBD2 + Phone):** `id, asset_id FK, lat/lng, speed, fuel_level, engine_fault_code, harsh_braking bool, created_at` - OBD2 dongle ₦25k reads fuel, fault.

### Workflow: 
Driver opens Driver App 6am → Must complete Inspection checklist + 5 photos → If fail → Fault ticket → Ops notified → Bus grounded. OBD2 mileage auto-updates → When reaches 5,000km → Maintenance Schedule auto-creates.

Disposal: After 4 years or 250k km, system flags: `status=disposal_due`, calculates resale value = purchase_cost - (depreciation*years).

---

## 12. GTFS & Google Transit Integration
(Previous Section 7 content retained - see below)


---

## 13. Road Sensor & Intelligence Module
(Retained from v2.0 - IRI calculation World Bank RoadLab, pothole clustering 5 reports within 20m=confirmed, map Green/Yellow/Red)

## 14. Wallet, Subsidy & Receipts
(Retained - dual balance cash+subsidy_credits, optimistic locking version, idempotent reference, 8 printable receipts with QR)

## 15. Regulatory, Legal & Competition Strategy
(Retained - NDPR hash only, Cooperative not taxi, insurance Leadway ₦100/trip, VAT only on commission, moat GTFS+verification+MDA)

## 16. International Adoption & Scaling
(Retained - multi-tenant country_id city_id, white-label City Pack)

## 17. PWA & Award-Winning UI System
(Retained - Linear+Stripe+Apple, Corridor Chip, Live Trip Card with 5-sec clip + IRI, Change Control Timeline, future AR pothole overlay, Voice, Haptics)

## 18. Build Sprints - Operations First (Updated)

Sprint1 Week1-2: Auth+Verification+Ops Control Tower skeleton (Filament) + WorkplaceSeeder 45 MDAs → Demo to MDA
Sprint2 Week3: Trip+Booking Atomic + Demand Calendar table + Duty Roster → Get 50 volunteer drivers
Sprint3 Week4: Asset+Maintenance+Inspection tables + Driver App checklist + OBD2 telemetry → Lease 3 buses, start pilot
Sprint4 Week5: Wallet Dual + Stakeholder Remittance + Daily Settlement Job → Finance ready
Sprint5 Week6: GTFS Publisher → Submit to Google, pitch World Bank
Sprint6 Week7: Road Sensor useRoadSensor.js + IRI + Heatmap → Pitch FERMA, sell Road API
Sprint7 Week8: PWA Award UI + Impact Certificates + Corporate Pass → Launch Green Challenge, PR, Angel round

## 19. Git & Version Control - Commit Every Phase

> **Policy:** Every phase (sprint) ends in exactly **one milestone commit + one tag**. Within a phase, commit small, logical units so each commit is self-contained, builds, and passes tests. This gives funders, auditors, and future developers a verifiable history: "Sprint 2 → `v0.2.0`, 68 tests green."

### 19.1 Repository Layout & Branching (Trunk-Based + Short-Lived Branches)
- `master` (or `main`) is the single source of truth and is always deployable.
- Feature work lives on `feature/<sprint>-<slug>` (e.g. `feature/sprint3-wallet`).
- Fixes live on `fix/<slug>`.
- A phase ends by merging its branch into `master` with a fast-forward or squash merge — the merge IS the milestone commit.
- Work on one phase at a time; never mix two phases in one branch/commit.

### 19.2 When to Commit — the "Definition of Done" Ritual
Run this checklist **before every commit**, and again at the phase-end milestone:

| Gate | Command (Windows) | Pass Criteria |
|------|-------------------|---------------|
| Format | `vendor\bin\pint` | 0 errors |
| Tests | `php artisan test` | all green |
| Build | `npm run build` | no errors |
| Docs | Update `DEVELOPMENT-LOG.md` | reflects this change |
| Stage | `git add <relevant files>` | only intended files |
| Inspect | `git status` / `git diff --cached` | no `.env`, no secrets |
| Commit | `git commit -m "<conventional message>"` | message explains WHY |
| Tag | `git tag -a v0.2.0 -m "..."` | only at phase end |

Rules:
- **Commit at two levels:** (a) after each small working unit that passes tests (small commits, easy to `bisect`), and (b) one milestone commit + tag at each sprint boundary.
- **Never stage `.env`, `*.key`, or any credential.** `.gitignore` already blocks `.env`; verify with `git status` before committing.
- Commit only files relevant to the change — no stray IDE/config noise.
- A good commit message describes the **why**, not just the what, so the history reads as a narrative for the 40% investor / 15% Trust audits.

### 19.3 Commit Message Standard — Conventional Commits
```
type(scope): subject
```
| Type | Use for | Example |
|------|---------|---------|
| `feat` | New capability | `feat(trips): publish trips with fixed corridor fares` |
| `fix` | Bug fix | `fix(api): gate booking on verification level` |
| `test` | Tests only | `test(booking): unverified API user cannot book` |
| `refactor` | Behavior-preserving change | `refactor(wallet): extract hold/capture/refund helpers` |
| `chore` | Tooling, deps, config | `chore: baseline WorkRide scaffold` |
| `docs` | Documentation only | `docs(guide): add git & version control workflow` |
| `perf` | Performance | `perf(matching): index corridor+departure_time query` |

Scope = module: `auth`, `verification`, `trips`, `bookings`, `wallet`, `chat`, `admin`, `gtfs`, `road`, `ops`, `guide`.

### 19.4 Tagging & Versioning — Tag = Sprint Milestone
- Annotated tags only (they carry a message for traceability):
  - `git tag -a v0.1.0 -m "Sprint 1: auth + verification + control tower"`
  - `git tag -a v0.2.0 -m "Sprint 2: trips + atomic bookings + reverb chat"`
  - `git tag -a v0.3.0 -m "Sprint 3: wallet dual balance + subsidy"` … and so on.
- A **release** = a green, tagged commit on `master`. Tag BEFORE pushing to the remote.

### 19.5 Traceability Rule — Records for Audits, Funders & Demos
- Every milestone commit updates `DEVELOPMENT-LOG.md`: status table, what changed, issues resolved, test counts, and the tag name (e.g. *"Sprint 2 complete — tag `v0.2.0` (68 tests, 217 assertions)"*).
- Debug a regression with `git bisect` between two tags.
- Pitch with the tag history: `git log --oneline --decorate` shows funders a clean, verifiable build-up.

### 19.6 Initial Baseline (repo has no commits yet — Sprint 1 + 2 done)
1. Confirm `.gitignore` blocks `.env` and build artifacts (`node_modules`, `vendor`, `public/build`, `.idea`).
2. `git add .`
3. Review with `git status` — verify `.env` is NOT staged.
4. Baseline commit:
   `git commit -m "chore: baseline — Laravel 13 scaffold + Sprint 1-2 (auth, verification, trips, bookings, chat)"`
5. Rename branch to `main` (or keep `master` per local convention): `git branch -M main`.
6. First tag = `v0.2.0` (the baseline already contains the complete, tested Sprint 2 state).

## Appendix: New Tables Added in v3.0 (18 additional)

- forecasts, stakeholder_remittances, unions, permits, assets, maintenance_schedules, inspections, faults, telemetry, duty_rosters, schedules, car_pool, car_pool_availability, driver_scores, fuel_advances, subsidy_reports, permits, gtfs_validations

Total Tables: 50+ (was 28) - Full operations + demand research ready.
New tables in v4.0: demand_surveys, junctions, probe_demand_points, od_surveys, demand_requests, od_matrix
