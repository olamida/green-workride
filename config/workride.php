<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WorkRide Configuration
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'email' => env('WORKRIDE_ADMIN_EMAIL', 'admin@workride.ng'),
        'password' => env('WORKRIDE_ADMIN_PASSWORD', 'admin1234'),
    ],

    // Demo accounts (DemoUserSeeder). Weak on purpose — demo-only credentials.
    'demo' => [
        'password' => env('WORKRIDE_DEMO_PASSWORD', 'demo1234'),
    ],

    'commission_rate' => env('WORKRIDE_COMMISSION_RATE', 0.10),

    // Union cooperative fee (NURTW/RTEAN) on paid rides — 5% per guide §10.
    'union_fee_rate' => env('WORKRIDE_UNION_FEE_RATE', 0.05),

    'insurance_per_trip' => env('WORKRIDE_INSURANCE_PER_TRIP', 100),

    'geofence_radius_m' => env('WORKRIDE_GEOFENCE_RADIUS_M', 500),

    // Trip matching: radius in metres around the passenger's location.
    'search_radius_m' => env('WORKRIDE_SEARCH_RADIUS_M', 2000),

    // Trip matching (API): only trips departing within this many minutes match
    // a live pickup request. Kept tight so near-term seats are not pre-empted.
    'departure_window_minutes' => env('WORKRIDE_DEPARTURE_WINDOW_MINUTES', 30),

    // Animated SVG brand cards (matching-anim, demand-map-anim, navigation-anim,
    // trip-fill-anim). Off by default — they are decorative, not functional, and
    // we want a calm, content-first UI until the site-wide animation language is
    // reviewed. Flip WORKRIDE_ANIMATIONS=true to re-enable them everywhere.
    'animations' => [
        'enabled' => (bool) env('WORKRIDE_ANIMATIONS', false),
    ],

    // Trip board (web): how far ahead the board shows trips so riders can plan
    // and book a seat a day ahead ("Kubwa → CBD, tomorrow 6:45am") and drivers
    // can see demand before they publish. 2880 minutes = 48 hours.
    'board_window_minutes' => env('WORKRIDE_BOARD_WINDOW_MINUTES', 2880),

    // Quick departure filters on the trip board. Each is minutes-from-now.
    'board_window_presets' => [
        'now' => 30,
        'later' => 240,
        'tomorrow' => 1440,
        'any' => 2880,
    ],

    // No-show policy: share of the held fare captured when a passenger no-shows.
    'no_show_capture_percent' => env('WORKRIDE_NO_SHOW_CAPTURE_PERCENT', 50),

    // Passenger-to-vehicle connect guide (last-mile). Walking is the core job
    // at informal junctions (Berger, Nyanya, Lugbe), so the guide is a calm 2D
    // follow view — not a city-wide turn-by-turn clone. `route_factor` inflates
    // straight-line distance toward a realistic walking path; the guide falls
    // back to that estimate when OSRM is unreachable (zero API cost).
    'guide' => [
        'enabled' => (bool) env('FEATURE_GUIDE', true),
        // Assumed walking speed for the "240 m · 3 min walk" ETA.
        'walking_speed_kmh' => env('WORKRIDE_GUIDE_WALKING_KMH', 5),
        // Walking distance = haversine × route_factor (road vs straight-line).
        'route_factor' => env('WORKRIDE_GUIDE_ROUTE_FACTOR', 1.25),
        // Below this distance the guide shows the "Wave — you are here" state.
        'arrived_radius_m' => env('WORKRIDE_GUIDE_ARRIVED_RADIUS_M', 50),
        // Client re-requests a walking route when either party moves this far.
        're_route_threshold_m' => env('WORKRIDE_GUIDE_REROUTE_THRESHOLD_M', 150),
        // Below this distance the follow view zooms in on the passenger.
        'zoom_close_m' => env('WORKRIDE_GUIDE_ZOOM_CLOSE_M', 150),
        // Map zoom presets (overview vs follow).
        'zoom_overview' => env('WORKRIDE_GUIDE_ZOOM_OVERVIEW', 14),
        'zoom_follow' => env('WORKRIDE_GUIDE_ZOOM_FOLLOW', 16),
    ],

    // Live junction progress (Sprint 3). Waypoint rows may override the
    // arrival radius per stop; ETA estimates degrade to straight-line at the
    // configured cruising speed when the routing provider is unreachable.
    'waypoint' => [
        // Default geofence radius (m) for waypoint arrival detection.
        'geofence_radius_m' => env('WORKRIDE_WAYPOINT_GEOFENCE_RADIUS_M', 100),
        // Assumed cruising speed (km/h) for straight-line ETA fallbacks.
        'avg_speed_kmh' => env('WORKRIDE_WAYPOINT_AVG_SPEED_KMH', 30),
    ],

    // Fixed per-corridor fares (anti-surge). Naira per seat.
    'max_fare_per_corridor' => [
        'kubwa_cbd' => 800,
        'nyanya_idu' => 700,
        'lugbe_cbd' => 600,
    ],

    // Approximate corridor distance (km) used for impact when a trip records
    // no measured route distance.
    'corridor_distance_km' => [
        'kubwa_cbd' => 22,
        'nyanya_idu' => 14,
        'lugbe_cbd' => 12,
    ],

    // Approximate corridor origin anchors for the map-first trip board.
    // Active trips pin at their live position; scheduled trips pin at these.
    'corridor_anchors' => [
        'kubwa_cbd' => ['lat' => 9.0093, 'lng' => 7.4619],
        'nyanya_idu' => ['lat' => 8.9435, 'lng' => 7.5190],
        'lugbe_cbd' => ['lat' => 9.0319, 'lng' => 7.4062],
        'cbd' => ['lat' => 9.0589, 'lng' => 7.4891],
        'idu' => ['lat' => 8.7490, 'lng' => 7.3580],
    ],

    // Navigation directions: how close a destination point must be to a
    // corridor's terminal anchor to count as "a ride going that way".
    'destination_match_radius_m' => env('WORKRIDE_DESTINATION_MATCH_RADIUS_M', 8000),

    // CO2 saved per passenger-km (kg).
    'co2_per_passenger_km' => env('WORKRIDE_CO2_PER_PASSENGER_KM', 0.12),

    // Trees equivalent per kg CO2.
    'trees_per_kg_co2' => env('WORKRIDE_TREES_PER_KG_CO2', 21),

    // Fuel economy: litres per km.
    'fuel_litres_per_km' => env('WORKRIDE_FUEL_LITRES_PER_KM', 0.10),

    // RoadLab IRI thresholds (m/km).
    'iri_thresholds' => [
        'excellent' => 4,
        'good' => 6,
        'fair' => 10,
    ],

    // Pothole confirmation: reports within X metres in Y hours.
    'pothole_confirm' => [
        'radius_m' => 20,
        'within_hours' => 72,
        'min_reports' => 5,
    ],

    // Google OAuth toggle. Disabled until GOOGLE_CLIENT_ID is set.
    'google_enabled' => (bool) env('GOOGLE_CLIENT_ID'),

    // GTFS static feed publisher settings.
    'gtfs' => [
        'agency_id' => env('WORKRIDE_GTFS_AGENCY_ID', 'WR'),
        'agency_name' => env('WORKRIDE_GTFS_AGENCY_NAME', 'WorkRide Staff Mobility'),
        'agency_lang' => env('WORKRIDE_GTFS_AGENCY_LANG', 'en'),
        'agency_timezone' => env('WORKRIDE_GTFS_AGENCY_TIMEZONE', 'Africa/Lagos'),
        // Feed covers 365 days forward from generation.
        'service_days' => env('WORKRIDE_GTFS_SERVICE_DAYS', 365),
        // Average corridor speed used to interpolate stop_times (km/h).
        'avg_speed_kmh' => env('WORKRIDE_GTFS_AVG_SPEED_KMH', 30),
        // Waypoints closer than this (m) to a catalog stop reuse its stop_id.
        'stop_match_radius_m' => env('WORKRIDE_GTFS_STOP_MATCH_RADIUS_M', 1500),
    ],

    // FCT boundary (approximate bounding box).
    'fct_bounds' => [
        'min_lat' => 8.60,
        'max_lat' => 9.40,
        'min_lng' => 6.90,
        'max_lng' => 7.70,
    ],

    // Routing: OSRM (self-hosted, free) primary, paid fallback capped+logged.
    // Open-source first: paid APIs are only used when the free path fails AND
    // the monthly budget has headroom (api_caps.monthly_naira).
    'routing' => [
        // 'osrm' | 'google' | 'mapbox'
        'primary' => env('WORKRIDE_ROUTING_PRIMARY', 'osrm'),
        'osrm_host' => env('OSRM_HOST', 'http://localhost:5000'),
        'osrm_timeout' => env('OSRM_TIMEOUT', 5),
        'use_google_fallback' => (bool) env('USE_GOOGLE_FALLBACK', false),
        'google_api_key' => env('GOOGLE_MAPS_API_KEY'),
        'google_cost_per_request' => env('GOOGLE_COST_PER_REQUEST', 20),
        'use_mapbox_premium' => (bool) env('USE_MAPBOX_PREMIUM', false),
        'mapbox_access_token' => env('MAPBOX_ACCESS_TOKEN'),
        'mapbox_cost_per_request' => env('MAPBOX_COST_PER_REQUEST', 25),
        // Free, open-source geocoding fallback (Nominatim / OSM) for the
        // navigation search box when the local junction+workplace catalog runs
        // dry. No API key; respectful User-Agent required by their policy.
        'nominatim_base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'geocode_countrycodes' => env('NOMINATIM_COUNTRYCODES', 'ng'),
    ],

    // Paid external API budget. Every paid call is logged to api_cost_logs and
    // refused once this monthly cap is spent.
    'api_caps' => [
        'monthly_naira' => env('WORKRIDE_MONTHLY_API_CAP_NAIRA', 20000),
    ],

    // Road sensor thresholds (Sprint 5).
    'road_sensor' => [
        // Accelerometer Z (gravity-corrected) above this = candidate pothole.
        'pothole_z_threshold' => env('WORKRIDE_POTHOLE_Z_THRESHOLD', 15),
        // World Bank RoadLab: IRI = alpha * RMS(acc_z) / speed + beta.
        'iri_alpha' => env('WORKRIDE_IRI_ALPHA', 2.0),
        'iri_beta' => env('WORKRIDE_IRI_BETA', 1.5),
        'max_speed_kmh' => env('WORKRIDE_ROAD_MAX_SPEED_KMH', 200),
    ],

    // Time-Bank: "Ride Now, Drive Later" community reciprocity engine (Sprint 3.5).
    // A rider who cannot pay cash owes seats instead; they repay by driving and
    // carrying passengers. Feature-gated so it can be piloted per-MDA.
    'time_bank' => [
        'enabled' => (bool) env('FEATURE_TIME_BANK', false),
        // Average corridor fare used to convert naira owed into seats owed.
        'avg_fare_per_seat' => env('WORKRIDE_AVG_FARE_PER_SEAT', 600),
        // Days after the ride before the owed seats fall overdue.
        'due_days' => env('WORKRIDE_RIDE_CREDIT_DUE_DAYS', 7),
        // Max seats a user may owe at once (blocks piling up debt).
        'max_owed_seats' => env('WORKRIDE_MAX_OWED_SEATS', 3),
        // Days before due_date the overnight job nudges the debtor once.
        'remind_within_days' => env('WORKRIDE_RIDE_CREDIT_REMIND_DAYS', 3),
    ],

    // P2P wallet transfer between verified colleagues (like PalmPay).
    'p2p' => [
        'daily_limit' => env('WORKRIDE_P2P_DAILY_LIMIT', 10000),
        // Sender must be NIN-verified (Level 2+) for amounts above this.
        'sender_level_threshold_amount' => env('WORKRIDE_P2P_SENDER_LEVEL_THRESHOLD_AMOUNT', 5000),
        // Cash transfers carry a 1% platform fee, minimum ₦10. Earned is free.
        'fee_cash_rate' => env('WORKRIDE_P2P_FEE_CASH_RATE', 0.01),
        'fee_cash_min' => env('WORKRIDE_P2P_FEE_CASH_MIN', 10),
    ],

    // Driver earnings withdrawal (Moniepoint — mocked while unconfigured).
    'payout' => [
        'min_amount' => env('WORKRIDE_PAYOUT_MIN_AMOUNT', 100),
        'max_amount' => env('WORKRIDE_PAYOUT_MAX_AMOUNT', 100000),
    ],

    // Corporate Mobility Programs (Sprint 8) — employers pay staff commutes.
    'employer_programs' => [
        'enabled' => (bool) env('FEATURE_EMPLOYER_PROGRAMS', false),
    ],

    // Tier-0 phone verification — the instant-booking entry gate. An OTP proves
    // the number is live so a new rider can book at the normal fixed fare before
    // formal KYC. Benefits (subsidy, employer coverage, ride credits, volunteer
    // rides, rewards) stay locked behind Level 1+.
    'phone_verification' => [
        'enabled' => (bool) env('FEATURE_PHONE_VERIFICATION', true),
        // How a long the code stays valid.
        'otp_ttl_minutes' => env('WORKRIDE_PHONE_OTP_TTL_MINUTES', 10),
        // Wrong-code attempts before the code is burned.
        'otp_max_attempts' => env('WORKRIDE_PHONE_OTP_MAX_ATTEMPTS', 5),
        // Minimum seconds between two sends to the same number.
        'send_cooldown_seconds' => env('WORKRIDE_PHONE_SEND_COOLDOWN_SECONDS', 60),
        // Maximum codes a user can request per day.
        'send_daily_limit' => env('WORKRIDE_PHONE_SEND_DAILY_LIMIT', 5),
        // Where the code goes: 'database' + 'log' now; plug an SMS channel later.
        'sms_channel' => env('WORKRIDE_SMS_ENABLED') ? env('WORKRIDE_SMS_PROVIDER', 'termii') : 'log',
    ],

    // Reward Campaigns + Green Points economy (Sprint 8).
    'rewards' => [
        'enabled' => (bool) env('FEATURE_REWARDS', false),
        // Core guide §6 Workflow 2: volunteer drivers earn green points per ride.
        'volunteer_green_points' => env('WORKRIDE_VOLUNTEER_GREEN_POINTS', 10),
        'green_points_naira_per_point' => env('WORKRIDE_GREEN_POINTS_NAIRA_PER_POINT', 5),
        'green_points_min_redeem' => env('WORKRIDE_GREEN_POINTS_MIN_REDEEM', 50),
    ],

    // Wallet-to-commodity commerce (Sprint 8) — gold, rice, maize, fuel.
    'commodities' => [
        'enabled' => (bool) env('FEATURE_COMMODITIES', false),
    ],

    // Promoted volunteer activities (Sprint 9 "Missions"). A promoter defines
    // an activity + reward; the app observes performance and pays out (auto
    // from real events, or after photo-proof review). Gated so it can be
    // piloted per MDA/corporate sponsor.
    'missions' => [
        'enabled' => (bool) env('FEATURE_MISSIONS', false),
    ],

    // Tiered KYC (Sprint 3.6) — open staff-ID liveness, licensed NIN lookup,
    // commercial driver anti-spoof. Tier 1 is free and always available once
    // the feature is on; Tiers 2/3 additionally need their provider enabled.
    'verification' => [
        'enabled' => (bool) env('FEATURE_LIVENESS', false),
        // Anti-brute-force: attempts per tier per day before HTTP 429.
        'attempts_per_day' => env('WORKRIDE_VERIFY_ATTEMPTS_PER_DAY', 5),
        // Tier-1 auto-approval threshold (client liveness is a signal, not a
        // gate: below this we drop to manual review instead of failing hard).
        'liveness_min_score' => env('WORKRIDE_LIVENESS_MIN_SCORE', 75),
        // NDPR retention: encrypted selfies are purged after this many days.
        'selfie_retention_days' => env('WORKRIDE_SELFIE_RETENTION_DAYS', 30),
        // Driver verification fee (₦) charged to cover the Tier-3 vendor cost.
        'driver_verification_fee_naira' => env('WORKRIDE_DRIVER_VERIFICATION_FEE', 500),
    ],

    // Demand research (guide §9B) — junction counts, OD surveys, probe points,
    // rider check-ins. On by default: it is the cheapest way to prove BRT
    // demand (₦50k interns + phones vs ₦100k consultants).
    'demand' => [
        'enabled' => (bool) env('FEATURE_DEMAND', true),
    ],

    // Fleet lifecycle (guide §11) — assets, daily inspections, faults, OBD2
    // telemetry. Asset-light: leases first, buy later.
    'fleet' => [
        'enabled' => (bool) env('FEATURE_FLEET', false),
    ],

    // EV lease-to-own schema seams (WORKRIDE-DESIGN-REVIEWS §4) — propulsion,
    // battery telemetry, lease agreements, charging stations. DEFER the
    // hardware; the finance layer is a fuel-price hedge, not a one-way bet.
    'ev_lease' => [
        'enabled' => (bool) env('FEATURE_EV_LEASE', false),
    ],

    // Stakeholder remittances (guide §10) — per-trip union shares. Make the
    // unions agents, not enemies.
    'stakeholders' => [
        'enabled' => (bool) env('FEATURE_STAKEHOLDER_REMITTANCES', false),
    ],

    // Demand forecasting (guide §9) — event multipliers over last-4-same-
    // weekday booking averages.
    'forecasts' => [
        'enabled' => (bool) env('FEATURE_FORECASTING', false),
        // Assumed seats per bus used to convert predicted demand into a
        // recommended number of extra vehicles.
        'seats_per_vehicle' => env('WORKRIDE_FORECAST_SEATS_PER_VEHICLE', 15),
    ],

    // Recurring supply backbone (guide §6 Workflow 5) — declarative
    // BusSchedule rows ("Kubwa→CBD every 15 min Mon–Fri 06:30–09:00")
    // materialised nightly into real, bookable Trip rows. On by default so
    // the board always shows the guaranteed timetable ahead of the live rides.
    'scheduling' => [
        'enabled' => (bool) env('FEATURE_SCHEDULING', true),
        // How many days ahead a driver's "Repeat Mon–Fri" carpool publishes
        // companion trips in one action.
        'repeat_horizon_days' => env('WORKRIDE_REPEAT_HORIZON_DAYS', 14),
        // Seat capacity used when a schedule's vehicle has no recorded seats.
        'default_seats' => env('WORKRIDE_SCHEDULE_DEFAULT_SEATS', 15),
        // How far ahead the board's "Next departures" panel looks.
        'lookahead_hours' => env('WORKRIDE_SCHEDULE_LOOKAHEAD_HOURS', 48),
    ],
];
