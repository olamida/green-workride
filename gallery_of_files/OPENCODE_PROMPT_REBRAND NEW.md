# MASTER PROMPT FOR OPENCODE — WorkRide Award-Grade Rebrand
### COPY EVERYTHING BELOW THIS LINE INTO OPENCODE

You are opencode, a junior AI code-writer. You are working inside an EXISTING Laravel 11 codebase called WorkRide. DO NOT rebuild from scratch. You will upgrade the UI/UX to Apple Design Award grade.

You are not smart, so you MUST follow this document exactly, step by step, in order. Do not skip. Do not invent.

---

### PART 0 - READ FIRST OR YOU WILL FAIL

Open these 3 files in your repo and read them FULLY before coding:

1. `WORKRIDE-APP-GUIDE.md` - 50+ tables, 8 revenue streams, dual-app, GTFS, Road Intelligence. This is product truth.
2. `WORKRIDE-DEV-GUIDE.md` - Engineering contract. Read Section 4 "Known Traps" 3 times. It lists bugs that happen if you compare enum to string, forget model $attributes, use $dispatch outside x-data, etc.
3. `WorkRide_Award-Grade_UIUX_Fun.txt` - The rebrand spec. This is your UI bible.

If you don't read them, you will break money logic.

---

### PART 1 - NON-NEGOTIABLES - NEVER VIOLATE OR YOU GET FIRED

1. **NEVER store raw NIN.** Only `nin_hash` (SHA256) + `nin_last4`. If you touch verification, leave NIN alone.
2. **Money = decimal(15,2). No floats.** Every hold/capture/refund/transfer MUST be: `DB::transaction()` + `Wallet::where(...)->lockForUpdate()->first()` + check `wallets.version` optimistic lock + unique idempotency reference like `BOOK-{bookingId}-HOLD`. Look at `app/Services/WalletService.php` and copy its pattern exactly.
3. **Feature flags:** Check `config/workride.php`. New UI behind flag if risky, but this rebrand is critical path - you can enable `FEATURE_*` = true only for UI flags you create. Never enable `FEATURE_TIME_BANK`, `FEATURE_FLEET`, `FEATURE_EMPLOYER_PROGRAMS` unless asked.
4. **Board = 48h ahead (2880 min), Matcher = 30 min near.** In `TripMatchingService`, `board()` shows 48h, `findMatches()` only 30 min. NEVER make matcher 48h or you steal near-term seats.
5. **Guest-safe:** Public routes `/`, `/road/map`, `/gtfs/*`, `/trips/{id}/share`, `/receipts/verify/*`, `/impact/verify/*`, `/offline` MUST use `layouts/public`. That layout does NOT call `auth()->user()`. If you call it unconditionally, guests get 500 error.
6. **Design tokens in ONE FILE ONLY:** `resources/css/design-system.css`. Never hardcode colors in Blade. Use `var(--wr-forest)` etc.
7. **Services own logic:** Controller = validate + call Service + return view. Service never returns RedirectResponse. Every trust action (booking, verification, wallet, SOS, rating) MUST write `activity_logs` row.
8. **Tests green:** After EVERY step run: `vendor/bin/pint`, `php artisan test`, `npm run build`. If any fails, FIX before next step.
9. **Conventional commits:** `feat(ui): map-first trips board` etc.
10. **No Google Maps clone:** Use Leaflet + OSM + OSRM. No Google Maps JS for core flow.

---

### PART 2 - WHAT THE REBRAND IS - APPLE DESIGN AWARD MINDSET

WorkRide's tagline: "Google Maps knows roads. Uber knows rides. WorkRide knows Abuja's junctions."

We target Apple Design Award categories in order:
1. Social Impact - fuel poverty, GTFS, CO2
2. Inclusivity - WCAG 2.2 AA, VoiceOver, Dynamic Type, Reduce Motion, 44pt touch targets
3. Interaction - map-first, one-handed, calm, 2 taps to book
4. Delight and Fun - purposeful 200ms spring motion only, no noise
5. Innovation - junction demand + RoadLab sensors + GTFS-Flex
6. Visuals - calm, hierarchical, glassmorphism

Mental model - simplify to 3 jobs only:
- **Get me there** = Trips / Map = Corridor chip → options → pay → live guide until connect
- **I drive/volunteer** = Publish + Fleet = inspection → publish → live location → board
- **My money & impact** = Wallet + Impact

Primary nav (mobile bottom tabs, desktop top nav):
1. Trips (map icon, default)
2. My Rides
3. Wallet
4. Impact
5. More

---

### PART 3 - STEP-BY-STEP IMPLEMENTATION - DO IN THIS EXACT ORDER

#### STEP 3.1 - DESIGN SYSTEM (DO FIRST)

File: `resources/css/design-system.css`

Add/replace tokens:

```css
@theme {
  --wr-forest: #2E7D32;
  --wr-forest-dark: #1B5E20;
  --wr-gold: #FBC02D;
  --wr-slate: #0F172A;
  --wr-paper: #F6F9F6;
  --wr-glass: rgba(255,255,255,0.72);
  --wr-glass-blur: 20px;
  --wr-shadow: 0 4px 24px rgba(15,23,42,0.06);
  --wr-radius-pill: 9999px;
  --wr-radius-card: 20px;
  --wr-motion: 200ms;
  --wr-spring: cubic-bezier(0.34,1.56,0.64,1);
  --wr-font-heading: 'Sora', sans-serif;
  --wr-font-body: 'Inter', sans-serif;
  --wr-font-mono: 'JetBrains Mono', monospace;
}
.wr-glass { backdrop-filter: blur(20px); background: var(--wr-glass); border: 1px solid rgba(255,255,255,0.3); box-shadow: var(--wr-shadow); border-radius: var(--wr-radius-card); }
.wr-chip { height: 60px; border-radius: var(--wr-radius-pill); padding: 0 20px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; min-width: 44px; min-height: 44px; }
.wr-focus:focus-visible { outline: 2px solid var(--wr-forest); outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; } }
.dark { --wr-paper: #0F172A; --wr-glass: rgba(15,23,42,0.72); }
```

- Ensure Sora, Inter, JetBrains Mono loaded via Google Fonts or local
- 8px grid, glass blur 20, 12% white, soft shadow
- Motion 200ms spring purposeful only, gated by `WORKRIDE_ANIMATIONS` flag if exists
- Then `npm run build`, commit: `feat(design): award-grade tokens forest/gold/slate/paper + glass + a11y`

#### STEP 3.2 - GLOBAL SHELL & NAV

Files:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/public.blade.php`
- Create `resources/views/components/bottom-nav.blade.php`
- Create `resources/views/components/top-nav.blade.php`

Requirements:
- Mobile: bottom tab bar fixed, 5 tabs, icons + labels, safe-area `pb-[env(safe-area-inset-bottom)]`, height 72px, glass background, active = forest fill, inactive slate-500, aria-current="page", touch 44x44 min
- Desktop: top nav horizontal same 5 items, logo left, profile right
- Public layout: NO auth()->user() unconditional. Show Sign in / Get started buttons
- All semantic nav, keyboard navigable, focus ring visible
- Test at 375px, 768px, 1280px

Commit: `feat(nav): Apple-like bottom tabs + top nav + safe-area`

#### STEP 3.3 - MAP-FIRST TRIPS BOARD (HIGHEST LEVERAGE)

Files:
- `resources/views/trips/index.blade.php` (REWRITE)
- Create `resources/views/components/corridor-chip.blade.php`
- Create `resources/views/components/trip-card.blade.php`
- `app/Services/TripMatchingService.php`
- `app/Events/BookingConfirmed.php`
- `routes/channels.php`
- `resources/js/app.js` (add Echo listeners)

Implementation:

A. Layout: 70% map top (Leaflet OSM centered [9.0579,7.4951] zoom 11 Abuja), 30% bottom sheet: horizontal scroll corridor chips + vertical trip list. Map and list sync.

B. Corridor Chip (HERO):
Props: corridorName, count, fare, isActive, leavingSoon (bool)
- 60px height pill, inactive = glass slate border, active = forest solid white text
- Text: `Kubwa→CBD • 12 • ₦600`
- If count>0 show gold pulse dot: `<span class="animate-pulse bg-[var(--wr-gold)] w-2 h-2 rounded-full"></span>` - disable animation under prefers-reduced-motion
- If leavingSoon show gold badge "Leaving soon"
- aria-label: "Kubwa to CBD, 12 trips, 600 naira"
- Click filters trips

C. Trip Card:
- Glass card rounded 20px, padding 16px
- Row1: badges: Live now (green), Book ahead (slate), Women-only (purple), Free (gold) - use tokens
- Row2: Seats left large mono `JetBrains Mono` 24px bold, driver name + stars from RatingService, plate
- Row3: Fare ₦600 mono + View & book button forest green
- Hover: translateY -2px, shadow larger, 200ms spring

D. Live seat counter (ROADMAP Gap 1.3):
- In `BookingService::book()` after DB commit, broadcast: `broadcast(new BookingConfirmed($trip->id, $trip->seats_remaining))->toOthers()`
- Create event `BookingConfirmed` with public channel `trips.{tripId}` data: trip_id, seats_remaining
- In `routes/channels.php`: allow all for now (or auth)
- In Blade Alpine: `Echo.channel('trips.'+tripId).listen('BookingConfirmed', e => { this.seats = e.seats_remaining; this.$refs.seat.classList.add('tick'); setTimeout(()=>...,150) })`
- CSS tick: scale 1.05 + color forest, 150ms

E. Leaving soon boost (Gap 1.4):
- In TripMatchingService board(): if window == 'now', sort: trips departing <=15min first: `orderByRaw("CASE WHEN departure_time <= ? THEN 0 ELSE 1 END", [now()->addMinutes(15)])` then distance, then departure_time
- Add test.

F. Demand-aware empty state (Gap 1.5):
- If no trips, query `demand_requests` + `forecasts` for that corridor tomorrow 7am: show "12 people want Kubwa→CBD tomorrow 7:00 — be the driver" + Publish CTA button

Run pint, test, build. Commit: `feat(trips): map-first + live chips + seat channel + leaving-soon + demand empty`

#### STEP 3.4 - BOOKING FLOW + CONNECT GUIDE (THE AWARD WINNER - DO NOT SKIP)

This is what wins Apple award. Must be perfect.

Files:
- `resources/views/trips/show.blade.php` (booking)
- Create `resources/views/components/payment-picker.blade.php`
- Create `resources/views/trips/guide.blade.php` NEW CRITICAL
- Create `resources/js/guide.js` Alpine component
- `app/Services/RoutingService.php` ensure method exists
- `routes/web.php` add `GET /trips/{trip}/guide` named `trips.guide`

**Booking Page:**

- Trip details header, corridor, driver card (name, rating, verification badge), seats
- Pickup row: default current location button "Use current location" -> navigator.geolocation.getCurrentPosition, fallback to waypoint select
- Payment picker component: large tappable rows 56px min:
  - Wallet (Cash ₦X, Subsidy ₦Y) show balances from WalletService
  - Cash "Pay driver directly"
  - Subsidy if eligible (check verification level)
  - Ride Credit if eligible (L2+ + vehicle)
  - Free Volunteer if is_free_volunteer
- Primary button: "Confirm seat • ₦X" forest green full width 56px height, loading spinner on submit, success check animation
- Micro-interactions: press scale 0.98 (transform scale), selection indicator checkmark, error calm: "Seat just taken. Try another."
- On success: confirmation panel then auto-redirect to guide if trip status active or departure <=30min: `window.location = route('trips.guide', trip.id)`

**Connect Guide - 3 States - IMPLEMENT EXACTLY:**

State 1 Overview:
- Full Leaflet map 100vh minus header
- Pins: Blue dot You = `L.divIcon({className:'you-pin', html:'<div class="w-4 h-4 bg-blue-500 rounded-full border-2 border-white shadow"></div>'})`, Green pin Vehicle = bus/car glyph + small verification badge, use forest color
- Polyline: Forest Green #2E7D32 weight 4, from You to Vehicle, get from RoutingService::route($fromLat,$fromLng,$toLat,$toLng) which uses OSRM first strategy, cache in api_cost_logs, return GeoJSON
- Bottom glass card  (rounded-t-[20px]): distance "240m • 3 min walk" large 20px bold, ETA, plate, Start Guide button forest large
- Fit bounds: `map.fitBounds(L.latLngBounds([you, vehicle, ...polylineLatLngs]), {padding:[80,80]})`
- Controls: recenter, locate me floating glass buttons

State 2 Active Follow (Micro):
- Trigger Start Guide click
- Transition: bottom card collapses to compact HUD 88px height, map eases: `map.flyTo(youLatLng,16,{duration:1})` duration 0 if matchMedia('(prefers-reduced-motion: reduce)').matches
- Follow: `navigator.geolocation.watchPosition` updates you pin, Reverb `Echo.channel('trips.'+tripId).listen('TripLocationUpdated', e=>{ vehiclePin.setLatLng([e.lat,e.lng]) })` every 10-15s from backend
- Re-route when you move >50m: throttle max 1 call per 10s, use Turf.js distance: `turf.distance(turf.point([oldLng,oldLat]), turf.point([newLng,newLat]))*1000` if >50 then call RoutingService via fetch `/api/route?from=...&to=...` (create endpoint if needed, but reuse existing)
- HUD compact: left large distance "240m • 3 min" 18px bold, center plate "ABJ-849-KJ" + verified badge, right Recenter + End Guide buttons 44px, text "Wave when you see the plate" when distance <150m
- Adaptive zoom: <150m zoom 16 else 14, keep both pins in view when possible
- Voice optional: `if(!muted && 'speechSynthesis' in window) speechSynthesis.speak(new SpeechSynthesisUtterance("240 meters"))` respect mute toggle
- Pins: you steady or very soft pulse (CSS animation scale 1->1.2->1 2s infinite), vehicle soft pulse while moving

State 3 Terminal:
- Arrived: distance <50m Haversine: in JS `function haversine(lat1,lon1,lat2,lon2){...}` <50. Show success: checkmark icon + "You're here — wave" + Board button. Map stops following. One-shot animation scale+fade 200-300ms. Auto-stop watchPosition and Echo.
- Missed: vehicle leaves geofence (distance >500m and increasing) or booking status no_show or trip completed without boarded. Show calm: "Guide stopped. Vehicle has left." + actions Re-book next trip (link to trips index same corridor), Contact driver (chat), Back to trips. No red flash loop, slate muted.

Accessibility MANDATORY:
- All buttons aria-label
- Live region `<div aria-live="polite" aria-atomic="true" id="guide-status" class="sr-only">` update distance and state
- VoiceOver flow must complete: Overview -> Start -> Follow -> Arrived/Missed
- Dynamic Type: test 200% font size, HUD must not break
- Reduced motion: no easing, no pin pulse, instant number updates, map jumps not flyTo
- Touch targets 44x44 min
- Contrast 4.5:1 polyline forest on paper passes, HUD text slate on glass passes

Packages:
`npm install leaflet-routing-machine lrm-osrm @turf/turf`

Backend:
- Ensure TripLocationUpdated broadcasts vehicle lat/lng
- Guide route controller checks auth and that user is passenger of trip
- Add API endpoint `GET /api/trips/{trip}/route?fromLat=&fromLng=&toLat=&toLng=` if not exists, calls RoutingService

Commit: `feat(guide): dynamic passenger-to-bus connect guide overview->active->terminal + Reverb + routing + a11y`

#### STEP 3.5 - MY RIDES, WALLET, IMPACT

Files:
- `resources/views/rides/index.blade.php` or `my-rides/index.blade.php`
- `resources/views/wallet/index.blade.php`
- `resources/views/impact/index.blade.php`
- `resources/views/profile/*`

My Rides:
- Segmented control Accessible tabs: Active | Upcoming | Past - use role="tablist" aria-selected
- Active card: mini Leaflet static thumbnail (use `L.map` with dragging false, or static image from OSM), + "Open Guide" button + status badge (boarded, upcoming, completed)
- Past: corridor, date, fare, CO2 saved (from Co2Service), Rate / Certificate buttons
- Empty: encouraging "No rides yet — find your first corridor" + Find a ride CTA

Wallet:
- 3 large glass balance cards Cash / Subsidy / Earned, mono numbers, icons
- Quick actions: Top up, Send, Withdraw large tappable 48px
- Transaction list: icon, description, amount green credit slate debit, date, status, receipt link `/receipts/verify/{type}/{ref}`
- Statement download button -> calls existing export or maatwebsite/excel

Impact:
- Large numbers CO2 saved, Fuel saved, Trees (CO2/21), Green Level ring/bar (use CSS conic-gradient or simple div)
- Prominent "Download CO2 Certificate" button with QR (use simplesoftwareio/simple-qrcode if installed)
- Optional collapsible workplace leaderboard (if data exists)
- Copy human: "Every shared ride is collective progress."

Profile / More:
- List: Profile & Safety, Demand check-in, Road map, My Fleet if driver (check driver.verified), Settings, Help, Sign out
- Profile: avatar, name, verification badges with explanations, emergency contact, women-only toggle (opt-in, never hard sort - toggle saves to profile preference, board filter defaults from it but does NOT force women-only trips to top)
- Demand check-in: map pin + destination + headcount + one-tap submit saves to demand_surveys table via DemandService
- Road heatmap public Green/Yellow/Red IRI layers + legend + worst segments list from RoadIntelligenceService

Commit: `feat(rider): My Rides + Wallet + Impact + Profile award-grade`

#### STEP 3.6 - DRIVER & PUBLISH

Files:
- `resources/views/trips/create.blade.php`
- `resources/views/driver/trip.blade.php`

Publish:
- Form: corridor chips component (reuse), time picker native datetime-local, seats stepper (+/- buttons 44px), vehicle selector dropdown of user's verified vehicles (from FleetService or vehicles table)
- Fixed fare display read-only from config/workride.php corridor_fares[corridor] = e.g. 600, no surge
- If FEATURE_FLEET=true, check latest inspection today pass else block with link to inspection page, failed inspection auto opens fault ticket (use FleetService)
- Large Publish button forest green 56px

Driver Live Trip:
- Map with passenger pickup pins
- Seat list with Board / No-show actions: Board calls BookingService::board() which captures hold, No-show triggers 50% capture rule (check BookingService)
- Chat entry link to trip chat
- Complete trip button triggers completeTrip -> CalculateImpactJob, repays Time-Bank credits

Commit: `feat(driver): publish + live trip award-grade`

#### STEP 3.7 - POLISH, DARK MODE, EMPTY/LOADING/ERROR

- Dark mode: ensure all screens use tokens so .dark switch works. Test Trips board, Guide, Booking, Wallet. High contrast.
- Empty states: every list has calm branded empty + action button (use paper bg, slate icon, forest CTA)
- Loading: skeleton shimmer using paper + slate-100, not spinner everywhere, brand colors
- Error: quiet actionable never blaming: "Seat just taken. Try another trip." with retry button
- Micro-interactions audit: Chip pulse, seat-tick highlight, guide transitions, terminal success. All respect prefers-reduced-motion.
- Accessibility pass: run Lighthouse axe, fix alt, labels, contrast, focus order, live regions. VoiceOver full flow.
- PWA: manifest theme-color #2E7D32, icons, offline graceful: show cached board + offline banner "You're offline — showing saved trips"
- Keep JS <50kb critical Rider path: measure `npm run build` output, mid-range Android 3G devtools throttling

Packages to install now (if not present):
`composer require maatwebsite/excel simplesoftwareio/simple-qrcode barryvdh/laravel-dompdf`
`npm install leaflet-routing-machine lrm-osrm @turf/turf`

Do NOT install Vue/React full rewrite, Google Maps JS, Lottie/Rive heavy.

Commit: `feat(polish): dark mode + empty/loading/error + a11y + PWA`

---

### PART 4 - DEFINITION OF DONE

After EACH step run:
```
vendor/bin/pint
php artisan test
npm run build
```

Must be green. If fails fix.

Then:
```
git add <relevant files only>
git status # no .env secrets
git commit -m "feat(ui): ..."
```

Update `DEVELOPMENT-LOG.md` with what changed.

Final checklist entire rebrand:

- [ ] Tokens in one file, dark mode works
- [ ] Bottom tabs + top nav safe-area 44px
- [ ] Map-first Trips 70/30, Corridor Chips 60px pulse live count, Trip cards glass 20px
- [ ] Live seat counter Reverb on BookingConfirmed
- [ ] Leaving soon boost <=15min now preset
- [ ] Demand-aware empty state from demand_requests/forecasts
- [ ] Booking calm payment picker Wallet/Cash/Subsidy/Ride Credit/Free, 56px rows, press scale
- [ ] Connect Guide 3 states Overview (blue you, green vehicle, forest polyline, glass card, fitBounds) -> Snap transition (card collapse, flyTo) -> Active Follow (follow you, vehicle Reverb 10-15s, compact HUD distance plate recenter end guide, zoom <150m, pulse, throttle re-route 10s, voice optional) -> Terminal Arrived <50m wave success / Missed calm
- [ ] My Rides segmented Active/Upcoming/Past mini-map + Open Guide
- [ ] Wallet 3 balances mono + quick actions + transaction list + receipts
- [ ] Impact CO2 Fuel Trees Level + Certificate QR
- [ ] Publish corridor chips fixed fare fleet gate + large Publish
- [ ] Driver live trip passenger pins Board/No-show Complete
- [ ] Empty/loading/error on every list, micro-interactions 200ms spring, reduced-motion respected
- [ ] Accessibility VoiceOver completes guide, Dynamic Type 200% not broken, focus rings, live regions, contrast 4.5:1
- [ ] PWA manifest offline graceful JS <50kb
- [ ] No raw NIN, money decimal FOR UPDATE version idempotency, guest-safe public layouts, tests green

If all checked, you succeeded. Tag `v1.0.0-rebrand` and push.

---

### FINAL REMINDER

Make map the quiet hero.
Make corridor chip the fastest path to action (1 tap).
Make Connect Guide the moment of trust that turns "I hope the bus is coming" into "I can see it and I will connect."
Everything else supports that loop without noise.
Calm, precise, trustworthy, Apple Design Award worthy, yet usable by civil servant on mid-range Android on 3G at 6:30am in Abuja.

END OF PROMPT
