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

    'commission_rate' => env('WORKRIDE_COMMISSION_RATE', 0.10),

    'insurance_per_trip' => env('WORKRIDE_INSURANCE_PER_TRIP', 100),

    'geofence_radius_m' => env('WORKRIDE_GEOFENCE_RADIUS_M', 500),

    // Trip matching: radius in metres around the passenger's location.
    'search_radius_m' => env('WORKRIDE_SEARCH_RADIUS_M', 2000),

    // Trip matching: only trips departing within this many minutes are shown.
    'departure_window_minutes' => env('WORKRIDE_DEPARTURE_WINDOW_MINUTES', 30),

    // No-show policy: share of the held fare captured when a passenger no-shows.
    'no_show_capture_percent' => env('WORKRIDE_NO_SHOW_CAPTURE_PERCENT', 50),

    // Fixed per-corridor fares (anti-surge). Naira per seat.
    'max_fare_per_corridor' => [
        'kubwa_cbd' => 800,
        'nyanya_idu' => 700,
        'lugbe_cbd' => 600,
    ],

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
];
