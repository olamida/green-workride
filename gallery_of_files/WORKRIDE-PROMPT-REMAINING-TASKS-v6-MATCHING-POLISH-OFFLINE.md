# WORKRIDE v6.0 — REMAINING TASKS AFTER v5.0 NAVIGATION-FIRST
## Filtered Gap Analysis + Refined AI Implementation Prompt

> **Context:** v5.0 (Sprints 1-4) already implemented: Navigation-first Home "Where are you going?", Leaflet fixes (fitBounds, labels, arrows), MapLibre tilt feature-flagged 35-55°, Live junction progress tracker via Reverb, Share-to-join link, Timing everywhere, Admin sidebar grouped into 5 packages + mobile + role switcher, Progress wizards, Bus scheduling FullCalendar + recurring job
> **This document:** Filters input_section.txt to show ONLY what is NOT yet implemented after v5, refines it to WorkRide's stack (Laravel 13, Blade+Alpine, Reverb, TripMatchingService, Wallet atomic, GTFS), and gives a single comprehensive AI coder prompt to implement ALL remaining tasks.

---

## PART 1 — FILTERED GAP ANALYSIS: Already Implemented vs New

### ✅ ALREADY IMPLEMENTED in v5.0 (Do NOT re-implement, only extend)

From input_section.txt §1-6 and your APP-GUIDE:
- Navigation-first Home with large search + Corridor Chips as shortcuts + Recent/Favourite
- Results screen: Join live, Book ahead, Share-link, Demand check-in, Publish
- Leaflet immediate fixes: fitBounds padding 40-80px, minZoom/maxBounds Abuja, CartoDB Positron tiles with labels, PolylineDecorator arrows, You + vehicle pins labelled, no empty zoom
- MapLibre GL JS feature-flagged: OpenFreeMap liberty style free, pitch 35-55° moderate, overview 0-20° → active 40-50° → arrived ease back, respects prefers-reduced-motion, fallback to Leaflet
- Live junction progress tracker: named junctions per corridor (Berger, Nyanya, Lugbe, Zuba, Suleja etc), geofence 100m crossing → WaypointReached event via Reverb, shared stepper visible to passenger/driver/admin
- Share-to-join link: private link + QR + public card (no live location) + request flow
- Timing everywhere: minutes to departure, ETA to pickup, ETA to each junction, walking time, 30s refresh, 500m geofence FCM
- Admin sidebar grouping: Operations (Live Trips, Demand, Fleet, Verifications, SOS), People (Users, Drivers, Employers), Intelligence (Road Map, GTFS, Impact, Reports), Business (Wallets, Subsidies, Settlements, API Keys, Time-Bank), System (Flags, Settings, Logs) — collapsible, hamburger + bottom nav mobile, tables Stack, touch >=44px
- Progress wizards: Booking (Destination→Options→Payment→Confirmed), Publish (Corridor→Time/Seats→Vehicle/Inspection→Publish), Verification upgrades
- Branding: design-system.css tokens Forest Green #0F5132 + Accent #FFC300, glassmorphism, Sora+Inter, same tokens Rider+Admin
- Bus scheduling basic: BusSchedule model, GenerateRecurringTripsJob daily 5am, FullCalendar admin, Leave now vs Leave later

### 🆕 NEW TASKS — Not Yet Implemented (To be built in v6.0)

These are from input_section.txt §2 Matching Algorithm + §3 Must-have polish + Nice-to-have, that v5 did NOT cover:

**A. Matching Algorithm Enhancements (High-Value, Core IP):**
1. Weighted Matching Score (corridor + time window + seats + verification level + historical reliability + women-only soft preference + road condition IRI + leaving-soon boost)
2. Live Seat Urgency — boost trips leaving ≤15 min with seats left
3. Demand → Supply Loop — when 8-12 demand check-ins same OD/time, surface "12 people want Kubwa→CBD at 07:00 — Be the driver" CTA
4. Soft Reservations — 2-3 min hold while passenger confirms payment/walks to point (prevents race)
5. Detour Tolerance — configurable +8-12 min detour budget so driver can pickup slightly off pure corridor, without breaking fixed-fare
6. Fairness & Rotation — avoid always giving same drivers best matches, new driver boost, light rotation

**B. Trust & Conversion Polish:**
7. Empty States that Create Supply — calm, demand-aware empty states that turn "no rides" into "create supply" CTA
8. Stronger Live Seat Counter — Reverb-driven with subtle tick animation (highlight seat decrement)
9. Women-only Preference — visible, opt-in, never hard filter that hides supply (soft boost)
10. SOS + Share Trip hardening — one-tap, audited, works offline-ish (queue + sync)

**C. Resilience & Reach:**
11. Impact & Certificates — make download prominent one-tap, QR-verifiable
12. Offline Resilience — cached corridor board + last-known trip status via Service Worker + IndexedDB
13. In-app Chat Reliability — message queue, retry, offline queue, read receipts
14. Recurring Trip Templates & Driver Favorites — driver can save "My Kubwa 6:30am Mon-Fri" template
15. Fleet Maintenance Calendar Richer — already have FleetService but need calendar view with 5,000km preventive + monthly
16. Localization Foundation — Hausa / Yoruba / Igbo strings for key actions (Where are you going? in 3 languages)
17. Public API Prep — Road Intelligence commercial endpoint structure (defer billing, but structure)
18. Native Shell Prep — Live Activities & haptics hooks (defer native, but add web hooks)

**Explicitly AVOID (per input_section.txt):**
- Full city-wide turn-by-turn car navigation (you are not Google Maps for driving)
- Heavy 3D / AR
- Complex dynamic pricing (keep fixed anti-surge)
- Replacing atomic wallet/seat logic

---

## PART 2 — REFINED COMPREHENSIVE AI PROMPT FOR ALL REMAINING TASKS (v6.0)

### Use this as single prompt for your AI coder (Cursor / Claude Code)

---

# PROMPT: WorkRide v6.0 — Matching Intelligence + Trust Polish + Offline Resilience

You are world-class Laravel 13 + Tailwind + Alpine + Reverb engineer. Your mission: implement v6.0 enhancements on top of v5.0 Navigation-First foundation. Do NOT invent parallel systems. Extend existing services: TripMatchingService, BookingService, WalletService, DemandService, FleetService, GeofenceService, RoutingService, RoadIntelligenceService.

**Non-negotiables (from DEV-GUIDE):**
1. Never store raw NIN — hash only
2. Money decimal(15,2) + SELECT FOR UPDATE + version optimistic locking + idempotent references (BOOK-{id}-HOLD, SHARE-{trip}-{passenger}-{ref}, HOLD-{booking}-{ts})
3. Board 48h, Matcher 30-min near-term window — never break
4. Design tokens only in design-system.css
5. Services own logic, controllers thin
6. Guest-safe layouts: public pages use layouts/public
7. pint + phpstan level 8 + test + build after each piece, update DEVELOPMENT-LOG.md
8. Keep JS reasonable, Blade+Alpine, no heavy SPA

### PHASE 1 — Matching Score & Live Urgency (Core IP)

**1.1 Weighted Matching Score — `app/Services/TripMatchingService.php` (ENHANCE)**

Current: Haversine 2km + corridor + time window. Enhance to weighted score 0-100:

```php
public function scoreTripForRequest(Trip $trip, array $request): array // request: lat,lng,dest_lat,lng,time, preferences
// Factors (weights sum 100):
// corridor_match 25pts: exact corridor =25, adjacent =10
// time_window 20pts: |departure - requested| <15min=20, <30=15, <60=5
// remaining_seats 15pts: seats>=3=15, 2=10,1=5
// verification_level 10pts: Level3 driver=10, Level2=5
// historical_reliability 10pts: driver completed_rate 90%+ =10, 70%+ =5 (from ImpactStat or driver_scores)
// women_only_soft 5pts: if request prefers women-only and trip is women-only +5 (soft boost, never hide)
// road_condition 5pts: trip road IRI good/excellent +5, fair +2, poor 0 (from RoadSegment via RoutingService)
// leaving_soon_boost 10pts: departure in ≤15min and seats>0 =10, ≤30min=5
// Return: ['score'=>85, 'breakdown'=>[...], 'reasons'=>['Leaving soon','Verified driver']]
```

- Sort Results screen by score DESC, show top reason chip "Leaving soon • Verified driver • Good road"
- Keep existing 30-min matcher rule for atomic booking, but board shows 48h sorted by score
- Add `trip_match_scores` to TripResource for debugging in admin

**1.2 Live Seat Urgency — `resources/views/components/trip/trip-card.blade.php` (ENHANCE)**

- If trip departure <=15min and seats >=1, show pulsing badge "Leaving soon • 2 seats left" with Accent Yellow + subtle pulse animation
- Reverb listener: When BookingConfirmed event, decrement seats live + tick animation: seat number briefly scales 1.2x + highlight Forest Green 500ms
- Implementation: Alpine `x-data="{seats: trip.seats}" @booking-confirmed.window="if($event.detail.trip_id==trip.id){seats--; $el.classList.add('tick'); setTimeout(()=>remove,500)}"`

**1.3 Fairness & Rotation — `TripMatchingService`**

- Add `last_assigned_at` to drivers, boost drivers not assigned in last 2h by +5 pts
- New driver boost: driver with <5 completed trips +5 pts for first week (retention)
- Avoid same top driver always winning: Add small randomization ±3 pts when scores within 5 pts tie

### PHASE 2 — Demand → Supply Loop + Empty States

**2.1 Demand Aggregation — `app/Services/DemandService.php` (ENHANCE)**

- New method `getHotspots(Carbon $window = now 2h): Collection` — groups demand_requests + probe_demand_points by junction + destination + hour bucket where count >=8
- Returns [{junction_name, destination, count, hour, avg_wait_time}]

**2.2 Empty State Component — `resources/views/components/ui/empty-state-demand.blade.php` (CREATE)**

Props: `junction`, `destination`, `count`, `type`

When Results screen has 0 trips for searched destination:
```blade
<div class="text-center p-8 bg-surface rounded-card">
  <div class="text-4xl mb-2">🚌</div>
  <h3 class="font-heading font-semibold">12 people want Kubwa→CBD at 07:00</h3>
  <p class="text-sm text-gray-500">No rides yet — be the first to help and earn Green Points + fuel credit</p>
  <div class="mt-4 flex gap-2 justify-center">
    <button class="btn-primary">Be the driver — Publish trip</button>
    <button class="btn-secondary">Join forming group — Check-in</button>
  </div>
</div>
```

- Use hotspots data: If hotspot exists for searched OD, show count. Else generic "Be first to publish"
- Driver CTA pre-fills publish form with that OD + time
- Passenger CTA creates demand check-in

**2.3 Soft Reservations — `app/Services/BookingService.php` (ENHANCE)**

- New status `soft_hold` in BookingStatus enum — 2-3 min hold while passenger confirms payment/walks
- Flow: User clicks Join → create booking status soft_hold with `soft_hold_expires_at = now+3min` + hold wallet (same as existing hold) + reserve seat (decrement available_seats)
- Frontend shows countdown "Seat held for 2:43 — Confirm payment" — progress wizard step
- Job `ReleaseExpiredSoftHoldsJob` runs every minute via scheduler: If soft_hold expired and not confirmed → refund hold + increment seat + status cancelled + Reverb event seat released
- Idempotency reference: `HOLD-{trip}-{passenger}-{ts}`

### PHASE 3 — Detour Tolerance + Women-only + Multi-stop Prep

**3.1 Detour Tolerance — `app/Services/RoutingService.php` + `TripMatchingService`**

- Config `workride.php`: `'detour_tolerance_minutes' => 12, 'detour_tolerance_km' => 3`
- When matching, allow trips where pickup is up to 3km / 12 min detour off pure corridor route, if driver has enabled "Allow slight detours" toggle in profile
- Calculate via OSRM: original route duration vs detoured route duration — if delta <= tolerance, include trip with score penalty -5 pts and label "Slight detour • +5 mins"
- UI: Show detour badge on trip card, never hide, user chooses
- Keep fixed-fare: No surge, fare unchanged even with detour (simplicity)

**3.2 Women-only Preference — `resources/views/components/trip/trip-card.blade.php` + `TripMatchingService`**

- Current: women-only exists but hard filter. Change to soft boost per 1.1
- Filter UI: Toggle "Women-only" in Results — when ON, boost women-only trips + show badge, but still show other trips below with label "Also available"
- Never hide supply — show women-only first, then others
- Trip publish: Checkbox "Women-only ride" — only visible if driver profile female (or allow any but with clear label)
- Safety: Keep existing block panel for non-female on women-only trips (from USER-GUIDE)

**3.3 Recurring Trip Templates — `app/Models/TripTemplate.php` (CREATE)**

```php
Schema: id, driver_id FK, name "My Kubwa 6:30am Mon-Fri", corridor, route_name, departure_time time, days json, vehicle_id, total_seats, fare_per_seat, waypoints json, is_active, created_at
```

- Driver can save current publish form as template
- My Templates page: List templates, one-click "Publish today" or "Publish this week"
- Service `TripTemplateService::publishFromTemplate(Template, date)` — reuses SchedulingService logic
- UI in publish flow: "Save as template" checkbox + "Use template" dropdown

### PHASE 4 — Trust Polish: Live Seat Counter + SOS + Impact + Chat

**4.1 Live Seat Counter with Tick — Already partially in 1.2, complete here**

- Ensure `TripPublished`, `BookingConfirmed`, `BookingCancelled` events broadcast via Reverb
- All trip cards listen and update seats live without refresh
- Add subtle sound/haptic option for driver when seat booked (Web Audio + navigator.vibrate(50))

**4.2 SOS + Share Trip Hardening — `app/Services/SosService.php` (ENHANCE)**

- SOS already exists per APP-GUIDE — enhance offline-ish: When offline, queue SOS in IndexedDB + Service Worker sync when back online
- Share Trip page `/trips/{trip}/share` already public — ensure no live location, only route + time + seats left, verification badge, QR code
- One-tap SOS button always visible on active trip (passenger+driver), writes audited alert, shows in Control Tower instantly via Reverb, also sends FCM + email to emergency contact

**4.3 Impact & Certificates — `resources/views/impact/index.blade.php` (ENHANCE)**

- Make download prominent one-tap: Big cards CO2 saved, Fuel saved, Trees, with "Download Certificate" button that generates PDF with QR (already have barryvdh/dompdf)
- QR verification already exists `/impact/verify/{user}/{type}` — ensure works
- Show impact on booking confirmed screen: "You saved 2.4kg CO2 with this ride!"

**4.4 In-app Chat Reliability — `app/Services/ChatService.php` (ENHANCE)**

- Current: per-trip chat via Reverb. Enhance: Message queue with retry, offline queue in IndexedDB, read receipts, typing indicator
- Add `is_delivered`, `is_read` to chat_messages table
- Frontend: Show "Sending... • Delivered • Read" + retry button on fail

### PHASE 5 — Resilience, Localization, Commercial Prep

**5.1 Offline Resilience — `public/sw.js` + `resources/js/offline.js` (ENHANCE)**

- Service Worker caches: corridor list, junction list, last trip board (48h), last known trip status for user's active trips, wallet balance
- IndexedDB `workride_offline` stores: pending bookings, demand check-ins, SOS, chat messages, road sensor events — sync when online via Background Sync API
- Offline page `/offline` shows cached board + "You are offline — bookings will sync when back"

**5.2 Fleet Maintenance Calendar Richer — `resources/views/admin/fleet/calendar.blade.php` (ENHANCE)**

- Already have FleetService with inspection gate, faults, maintenance — add calendar view with FullCalendar: shows preventive 5,000km + monthly schedules, overdue red, upcoming yellow
- Ground/repair/resolve actions from calendar

**5.3 Localization Foundation — `resources/lang/` (CREATE)**

- Create `en`, `ha`, `yo`, `ig` folders with `navigation.php` lang file: "Where are you going?" => "Ina za ka je?" (Hausa), "Nibo ni o n lo?" (Yoruba), "Ebee ka ị na-aga?" (Igbo)
- Add language switcher in More menu, store in session + user preference
- Start with 20 keys: Where to, Search, Join Ride, Book, Live, Seats left, Leaving soon, ETA, etc.
- Use `__()` helper in Blade

**5.4 Public API Prep (Structure Only) — `routes/api.php` + `app/Http/Controllers/Api/V1/Public/RoadIntelligenceController.php`**

- Structure for commercial: `GET /api/v1/public/road-segments?bbox=&condition=` returns anonymized IRI + condition (no user_id), rate limited, API key via `api_cost_logs` monthly cap already exists
- No billing yet, just structure + docs in `WORKRIDE-APP-GUIDE.md` §8

### DEFINITION OF DONE FOR v6.0

- [ ] Matching score weighted: corridor 25 + time 20 + seats 15 + verification 10 + reliability 10 + women-only soft 5 + IRI 5 + leaving-soon 10, with breakdown visible in admin, sorted in Results
- [ ] Live seat urgency: "Leaving soon" pulsing badge + Reverb tick animation on seat decrement, fairness rotation + new driver boost
- [ ] Demand→Supply loop: Hotspots >=8 same OD/time → empty state shows "12 people want X→Y at 07:00 — Be driver" CTA, pre-fills publish
- [ ] Empty states: Calm, demand-aware, always CTA to create supply, never dead-end
- [ ] Soft reservations: 3-min hold with countdown, wallet hold, seat reserved, auto release job every minute, idempotent HOLD- ref
- [ ] Detour tolerance: Config 12min/3km, driver toggle, OSRM delta check, badge "Slight detour +5 mins", fixed-fare unchanged
- [ ] Women-only: Soft boost, toggle shows women-only first but still shows others below, never hides supply
- [ ] Recurring templates: Driver can save "My Kubwa 6:30am Mon-Fri", one-click publish today/week
- [ ] Live seat counter: Reverb-driven, tick animation, optional haptic
- [ ] SOS + Share: One-tap audited, offline queue, public share card no live location + QR, Control Tower instant via Reverb
- [ ] Impact: One-tap certificate download with QR, shows on booking confirmed
- [ ] Chat: Queue, retry, offline queue, delivered/read receipts, typing indicator
- [ ] Offline: SW caches board + last trip status + wallet, IndexedDB queues pending actions, sync when online, offline page
- [ ] Fleet calendar: FullCalendar view with preventive + monthly, ground/repair/resolve
- [ ] Localization: ha/yo/ig for 20 keys, switcher in More, stored
- [ ] Public API structure: /public/road-segments anonymized, rate limited, api_cost_logs
- [ ] All non-negotiables intact: money decimal + FOR UPDATE + version + idempotency + NIN hash + activity_logs + 48h board/30m matcher + design tokens
- [ ] Tests green: pint + phpstan level 8 + test (including new matching score, soft hold expiry, detour, demand hotspot, offline queue) + build + migrate:fresh --seed <30s with 45 junctions + gtfs:generate valid
- [ ] DEVELOPMENT-LOG.md updated, commits conventional

### COMMIT SEQUENCE

```
feat(matching): weighted score + leaving-soon boost + fairness rotation
feat(demand): hotspots aggregation + empty states that create supply
feat(booking): soft reservations 3-min hold + release job
feat(routing): detour tolerance 12min/3km + driver toggle
feat(trip): women-only soft boost + recurring templates
feat(live): seat counter tick animation + haptic
feat(sos): offline queue + share hardening
feat(impact): one-tap certificate download
feat(chat): reliability queue + read receipts
feat(offline): SW caching board + IndexedDB sync
feat(fleet): maintenance calendar FullCalendar
feat(i18n): ha/yo/ig foundation 20 keys
feat(api): public road intelligence structure
```

End of v6.0 prompt.

---

## HOW TO USE

1. After v5.0 sprints done and tagged v5.0-navigation-first, give this file to AI coder
2. Say: "Execute PHASE 1 now. After DoD, ask confirmation before PHASE 2."
3. After all phases, tag v6.0-matching-intelligence
4. This completes input_section.txt gap analysis — you now have navigation-first + award-grade matching + trust polish + offline resilience — ready for FCTA + Google.org + MIT Solve

