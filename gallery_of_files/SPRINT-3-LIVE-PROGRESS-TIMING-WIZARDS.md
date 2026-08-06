# SPRINT 3 PROMPT: Live Progress + Timing + Wizards + Share Flow
## AI Coder Prompt — Copy-Paste Ready

> **Sprint:** 3 of 4 — Live & Trust
> **Goal:** Passenger/driver/admin see same trip progress through junctions, timing indicators everywhere, progress wizards, share-to-join request flow
> **Base:** input_section.txt §3,5 + WORKRIDE-APP-GUIDE §4 + DEV-GUIDE §6 Services
> **DoD:** Live trip progress visible, ETA everywhere, wizards on booking/publish, share request works

### ROLE
You are Senior Realtime + UX Engineer. You shipped Uber live tracking, Transit app progress. You know Reverb broadcasting, Carbon timing, Alpine wizards.

### TASKS:

#### 3.1 Live Trip Progress Through Junctions — `TripWaypoint` Upgrade + Service

**Migration `*_enhance_trip_waypoints_for_progress.php`:**
```php
Schema::table('trip_waypoints', function($table){
  $table->integer('eta_minutes')->nullable()->comment('ETA from origin in mins');
  $table->boolean('is_major_hub')->default(false);
  $table->decimal('distance_from_origin_km', 6,2)->nullable();
  $table->integer('geofence_radius_m')->default(100);
  $table->timestamp('reached_at')->nullable();
});
```

**Service `TripService::calculateProgress(Trip $trip): array` (UPDATE):**
- For each waypoint, compute distance from origin using Haversine via GeofenceService, ETA using RoutingService (distance/speed) + current traffic
- Return progress array: [{label, lat/lng, sequence, is_major_hub, eta, distance, status: passed|current|upcoming, reached_at}]
- Status logic: If current_lat/lng within geofence_radius of waypoint → mark as reached, fire event `WaypointReached`

**Events (UPDATE/CREATE):**
- `app/Events/TripLocationUpdated` — already exists — ensure broadcasts current_lat/lng + progress array
- `app/Events/WaypointReached` — new — broadcasts trip_id, waypoint_id, label, reached_at — triggers Reverb
- Listeners: Update trip_waypoints.reached_at, create activity_log, notify passengers via FCM "Bus now at Berger Junction"

**Shared Progress Tracker Component — `resources/views/components/trip/progress-tracker.blade.php`:**

Props: `progress` array, `orientation` horizontal|vertical

```blade
<div class="flex" :class="orientation=='vertical'?'flex-col':'flex-row'">
  @foreach($progress as $i => $p)
    <div class="flex items-center">
      <div class="w-8 h-8 rounded-full flex items-center justify-center
        @if($p['status']=='passed') bg-[var(--color-primary)] text-white
        @elseif($p['status']=='current') bg-[var(--color-accent)] animate-pulse ring-4 ring-[var(--shadow-live)]
        @else bg-gray-200 @endif">
        @if($p['status']=='passed') ✓ @else {{$i+1}} @endif
      </div>
      <div class="ml-2">
        <div class="text-sm font-semibold">{{$p['label']}} @if($p['is_major_hub']) <span class="badge">Hub</span> @endif</div>
        <div class="text-xs text-gray-500">{{$p['eta'] ? $p['eta'].' ETA' : ''}} {{$p['distance_from_origin_km']}}km</div>
      </div>
      @if(!$loop->last)<div class="w-8 h-0.5 bg-gray-200 @if($p['status']=='passed') bg-[var(--color-primary)] @endif"></div>@endif
    </div>
  @endforeach
</div>
```

Use in 3 places:
- Passenger: `resources/views/trips/show.blade.php` + Connect Guide
- Driver: `resources/views/driver/trips/live.blade.php`
- Admin: `resources/views/admin/trips/live.blade.php`

#### 3.2 Timing Indicators Everywhere

**Service `TripService::getTimingAttributes(Trip $trip, ?User $userForPickup): array`:**
```php
return [
  'minutes_to_departure' => now()->diffInMinutes($trip->departure_time, false), // negative if past
  'eta_to_pickup' => $this->routing->eta($trip->current_latlng, $userPickupLatLng),
  'eta_to_destination' => $this->routing->eta($trip->current_latlng, $trip->destination),
  'eta_to_next_waypoint' => ...,
  'delay_minutes' => $trip->actual_departure? $trip->actual_departure->diffInMinutes($trip->departure_time) : 0,
  'time_to_pickup_walk' => '8 mins walk' // via OSRM walking profile
];
```

**Display patterns:**
- Before trip: "Leaves in 12 mins" (badge), "Driver arrives in 5 mins (500m away)" (use GeofenceService 500m), "Time to get to pickup: 8 mins walk"
- During trip: "ETA Secretariat 7:10am (12 mins left)", "Next: Wuse Market in 4 mins", "Delayed 5 mins due to traffic" (if delay >5)
- Bus scheduling: "Bus every 15 mins peak, 30 mins off-peak. Next: 7:30am"

**Frontend:** Alpine `x-data="{eta: {...}}" x-init="setInterval(async()=>{eta = await fetchTiming()}, 30000)"` — refresh every 30s, plus Reverb listener for instant update

**FCM Push:** When driver enters 500m geofence of passenger pickup → `NotificationService::driverArriving()` → push "Driver arriving in 2 mins"

#### 3.3 Progress Wizards / Steppers — `resources/views/components/ui/progress-wizard.blade.php`

Props: `steps` array [{label, description}], `current` int, `showTime` bool

```blade
<div class="flex justify-between mb-6">
  @foreach($steps as $idx => $step)
    <div class="flex flex-col items-center flex-1">
      <div class="w-8 h-8 rounded-full {{$idx<$current?'bg-primary text-white':($idx==$current?'bg-accent ring-4':'bg-gray-200')}}">{{$idx+1}}</div>
      <div class="text-xs mt-1">{{$step['label']}}</div>
      @if($showTime && $step['eta'])<div class="text-[10px] text-gray-500">{{$step['eta']}}</div>@endif
    </div>
    @if(!$loop->last)<div class="flex-1 h-0.5 mt-4 {{$idx<$current?'bg-primary':'bg-gray-200'}}"></div>@endif
  @endforeach
</div>
```

Apply to:

1. **Booking flow:** `resources/views/bookings/create.blade.php` — Steps: Destination → Options → Payment → Confirmed — show timing at each: "Payment holds seat for 5 mins"
2. **Driver Publish flow:** `resources/views/trips/publish.blade.php` — Steps: Corridor → Time & Seats → Vehicle/Inspection → Publish — show forecast: "Friday 2:30pm high demand expected" from ForecastService
3. **Verification upgrades:** `resources/views/verify/*` — Phone → Workplace → NIN → Driver docs
4. **Connect Guide:** Mini walking progress Walking → Approaching → Arrived

- Must be accessible: `aria-current`, VoiceOver announces step changes, respects `prefers-reduced-motion`
- Mobile: Horizontal scroll or compact dots if >4 steps

#### 3.4 Share-to-Join Request Flow (Secure)

Current share link allows anyone to join — need request approval for safety (passenger already on trip shares to colleague).

Flow:
1. Passenger A on trip clicks Share → generates link `/trips/{id}/share?ref=A` + WhatsApp share button using Web Share API
2. Passenger B opens link (public page) → sees trip summary, live map, seats left → clicks "Request to Join"
3. Creates `Booking` with status `requested` + `referred_by = A` + `share_code`
4. Driver + Passenger A get Reverb + FCM notification "John wants to join at Berger"
5. Driver approves → status `confirmed` → atomic seat decrement (existing BookingService logic) → B gets confirmed
6. If seats full → request auto queued as waiting list

- Add column `bookings.referred_by_user_id` FK, `share_code`
- Update `BookingService::book()` to handle share referral + idempotency reference `SHARE-{trip}-{passenger}-{ref}`

### ACCEPTANCE:
- Live trip page shows progress tracker through junctions (Berger, Banex, Wuse...) with current highlighted, past checked, future outlined — visible to passenger, driver, admin same component
- WaypointReached event fires when vehicle crosses 100m geofence, updates reached_at, broadcasts via Reverb, all clients update without refresh
- Timing indicators everywhere: "Leaves in 12 mins", "ETA 7:10am (12 mins left)", "Next: Wuse in 4 mins", "Time to pickup: 8 mins walk"
- FCM push when driver enters 500m geofence
- Progress wizards on booking (4 steps) and publish (4 steps) and verification, with timing hints
- Share link request flow works: A shares → B requests → driver approves → seat atomically decremented, referral logged
- Tests: WaypointReached, timing attributes, share referral, booking wizard steps

### COMMIT:
`feat(nav): sprint 3 live junction progress + timing + wizards + share-to-join`
