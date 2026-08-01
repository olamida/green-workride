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

    // FCT boundary (approximate bounding box).
    'fct_bounds' => [
        'min_lat' => 8.60,
        'max_lat' => 9.40,
        'min_lng' => 6.90,
        'max_lng' => 7.70,
    ],
];
