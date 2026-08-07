-- WORKRIDE 45 JUNCTIONS SEEDING — Abuja & Environs High-Traffic Hotspots
-- For v5 Navigation-First + v6 Demand→Supply Loop testing
-- Source: Real commuter hubs where 500-5000 wait daily 5:30-9am
-- Usage: php artisan db:seed --class=JunctionSeeder OR import this SQL

-- Table structure expected:
-- junctions: id, name, slug, lat, lng, corridor ENUM(kubwa_cbd,nyanya_idu,lugbe_cbd,garki_wuse), union_id, photo_path, avg_wait_time_mins, is_major_hub bool, passenger_volume_daily int, state ENUM(FCT,Nasarawa,Niger), is_active bool, created_at, updated_at

INSERT INTO junctions (name, slug, lat, lng, corridor, passenger_volume_daily, is_major_hub, state, avg_wait_time_mins, is_active, created_at, updated_at) VALUES

-- A. KUBWA AXIS (Kubwa-CBD Corridor) — Primary, heaviest
('Kubwa Junction / Village Market', 'kubwa-junction', 9.1500, 7.3333, 'kubwa_cbd', 2500, true, 'FCT', 25, true, NOW(), NOW()),
('Kubwa FHA Junction', 'kubwa-fha', 9.1650, 7.3300, 'kubwa_cbd', 1200, false, 'FCT', 20, true, NOW(), NOW()),
('Kubwa Second Gate', 'kubwa-second-gate', 9.1550, 7.3400, 'kubwa_cbd', 1000, false, 'FCT', 18, true, NOW(), NOW()),
('Dutse Alhaji Junction', 'dutse-alhaji', 9.1200, 7.3800, 'kubwa_cbd', 1500, false, 'FCT', 22, true, NOW(), NOW()),
('Dutse Baupma Junction', 'dutse-baupma', 9.1100, 7.3900, 'kubwa_cbd', 800, false, 'FCT', 15, true, NOW(), NOW()),
('Bwari Junction', 'bwari-junction', 9.2833, 7.3800, 'kubwa_cbd', 700, false, 'FCT', 20, true, NOW(), NOW()),
('Dei-Dei Junction / Market', 'dei-dei-junction', 9.1100, 7.2800, 'kubwa_cbd', 1800, true, 'FCT', 30, true, NOW(), NOW()),

-- B. NYANYA-MARARABA AXIS (Nyanya-Idu Corridor) — 2nd heaviest, Nasarawa commuters
('Nyanya Under-Bridge', 'nyanya-under-bridge', 8.9800, 7.5800, 'nyanya_idu', 5000, true, 'FCT', 35, true, NOW(), NOW()),
('Mararaba Junction', 'mararaba-junction', 8.9700, 7.5900, 'nyanya_idu', 4000, true, 'Nasarawa', 30, true, NOW(), NOW()),
('Masaka Junction', 'masaka-junction', 8.9500, 7.6500, 'nyanya_idu', 2000, false, 'Nasarawa', 25, true, NOW(), NOW()),
('One Man Village / Keffi Road', 'one-man-village', 8.9000, 7.7000, 'nyanya_idu', 1200, false, 'Nasarawa', 20, true, NOW(), NOW()),
('Karshi Junction', 'karshi-junction', 8.8500, 7.5500, 'nyanya_idu', 600, false, 'FCT', 18, true, NOW(), NOW()),
('Karu Junction', 'karu-junction', 8.9900, 7.5700, 'nyanya_idu', 1500, false, 'FCT', 22, true, NOW(), NOW()),
('Jikwoyi Junction', 'jikwoyi-junction', 8.9700, 7.5600, 'nyanya_idu', 1800, false, 'FCT', 24, true, NOW(), NOW()),
('Kurudu Junction', 'kurudu-junction', 8.9600, 7.5400, 'nyanya_idu', 1000, false, 'FCT', 18, true, NOW(), NOW()),
('Orozo Junction', 'orozo-junction', 8.9300, 7.5200, 'nyanya_idu', 800, false, 'FCT', 15, true, NOW(), NOW()),

-- C. LUGBE-AIRPORT ROAD AXIS (Lugbe-CBD Corridor) — Fast growing
('Lugbe Junction / Across', 'lugbe-junction', 8.9600, 7.3800, 'lugbe_cbd', 2000, true, 'FCT', 25, true, NOW(), NOW()),
('Lugbe FHA', 'lugbe-fha', 8.9500, 7.3700, 'lugbe_cbd', 1200, false, 'FCT', 20, true, NOW(), NOW()),
('Lugbe Shoprite / Total', 'lugbe-shoprite', 8.9550, 7.3750, 'lugbe_cbd', 900, false, 'FCT', 15, true, NOW(), NOW()),
('Kuje Junction', 'kuje-junction', 8.8800, 7.2300, 'lugbe_cbd', 1000, false, 'FCT', 20, true, NOW(), NOW()),
('Gwagwalada Junction', 'gwagwalada-junction', 8.9400, 7.0800, 'lugbe_cbd', 1500, true, 'FCT', 25, true, NOW(), NOW()),
('Giri Junction', 'giri-junction', 8.9200, 7.1500, 'lugbe_cbd', 600, false, 'FCT', 15, true, NOW(), NOW()),
('Airport Toll Gate / Bill Clinton Drive', 'airport-toll-gate', 9.0060, 7.2700, 'lugbe_cbd', 800, false, 'FCT', 18, true, NOW(), NOW()),
('Aco Estate Junction', 'aco-estate', 8.9450, 7.3600, 'lugbe_cbd', 700, false, 'FCT', 15, true, NOW(), NOW()),
('Pyakasa Junction', 'pyakasa-junction', 8.9350, 7.3500, 'lugbe_cbd', 600, false, 'FCT', 12, true, NOW(), NOW()),

-- D. ZUBA-SULEJA AXIS (North-West, Niger State commuters) — Critical for v6 demand loop
('Zuba Junction', 'zuba-junction', 9.1000, 7.2100, 'kubwa_cbd', 1500, true, 'FCT', 28, true, NOW(), NOW()),
('Suleja Junction', 'suleja-junction', 9.1800, 7.1700, 'kubwa_cbd', 2000, true, 'Niger', 30, true, NOW(), NOW()),
('Madalla Junction', 'madalla-junction', 9.1300, 7.2000, 'kubwa_cbd', 1200, false, 'Niger', 22, true, NOW(), NOW()),
('Dakwa Junction / Dei-Dei to Zuba Road', 'dakwa-junction', 9.1200, 7.2500, 'kubwa_cbd', 800, false, 'FCT', 18, true, NOW(), NOW()),
('Tafa Junction', 'tafa-junction', 9.2500, 7.2500, 'kubwa_cbd', 600, false, 'Niger', 20, true, NOW(), NOW()),

-- E. CBD & CITY CENTER (Destinations)
('Berger Junction', 'berger-junction', 9.0820, 7.4450, 'kubwa_cbd', 3500, true, 'FCT', 20, true, NOW(), NOW()),
('Banex Junction', 'banex-junction', 9.0800, 7.4300, 'garki_wuse', 1200, false, 'FCT', 15, true, NOW(), NOW()),
('Wuse Market Junction', 'wuse-market', 9.0630, 7.4530, 'garki_wuse', 1800, true, 'FCT', 18, true, NOW(), NOW()),
('Area 1 Junction', 'area1-junction', 9.0430, 7.4850, 'garki_wuse', 1500, false, 'FCT', 15, true, NOW(), NOW()),
('Area 3 Junction', 'area3-junction', 9.0350, 7.4900, 'garki_wuse', 800, false, 'FCT', 12, true, NOW(), NOW()),
('Apo Junction / Mechanic Village', 'apo-junction', 8.9900, 7.5000, 'lugbe_cbd', 1200, false, 'FCT', 15, true, NOW(), NOW()),
('AYA Junction / Asokoro', 'aya-junction', 9.0500, 7.5200, 'nyanya_idu', 2500, true, 'FCT', 22, true, NOW(), NOW()),
('Mabushi Junction', 'mabushi-junction', 9.0700, 7.4300, 'kubwa_cbd', 1000, false, 'FCT', 12, true, NOW(), NOW()),
('Jabi Lake Junction / Motor Park', 'jabi-lake', 9.0650, 7.4200, 'kubwa_cbd', 1200, false, 'FCT', 15, true, NOW(), NOW()),
('Gwarimpa Gate / 3rd Gate', 'gwarimpa-gate', 9.1000, 7.4100, 'kubwa_cbd', 2200, true, 'FCT', 20, true, NOW(), NOW()),
('Kado Junction', 'kado-junction', 9.0900, 7.4200, 'kubwa_cbd', 600, false, 'FCT', 10, true, NOW(), NOW()),
('Utako Junction / Market', 'utako-junction', 9.0800, 7.4350, 'kubwa_cbd', 1500, false, 'FCT', 15, true, NOW(), NOW()),
('Gudu Junction', 'gudu-junction', 9.0200, 7.4900, 'garki_wuse', 500, false, 'FCT', 10, true, NOW(), NOW()),
('Durumi Junction', 'durumi-junction', 9.0300, 7.4600, 'garki_wuse', 600, false, 'FCT', 12, true, NOW(), NOW()),
('Galadimawa Junction', 'galadimawa-junction', 8.9700, 7.4200, 'lugbe_cbd', 700, false, 'FCT', 12, true, NOW(), NOW()),
('Lokogoma Junction', 'lokogoma-junction', 8.9600, 7.4500, 'lugbe_cbd', 900, false, 'FCT', 15, true, NOW(), NOW()),

-- F. OTHER HIGH-DENSITY
('Mpape Junction', 'mpape-junction', 9.0900, 7.5000, 'kubwa_cbd', 1000, false, 'FCT', 18, true, NOW(), NOW()),
('Karmo Junction', 'karmo-junction', 9.0400, 7.3800, 'kubwa_cbd', 800, false, 'FCT', 15, true, NOW(), NOW()),
('Idu Junction / Train Station', 'idu-junction', 9.0522, 7.3245, 'nyanya_idu', 1200, true, 'FCT', 20, true, NOW(), NOW()),
('Life Camp Junction', 'life-camp-junction', 9.0800, 7.4000, 'kubwa_cbd', 600, false, 'FCT', 12, true, NOW(), NOW()),
('Karsana / Kubwa Express Road', 'karsana-junction', 9.1300, 7.3500, 'kubwa_cbd', 500, false, 'FCT', 10, true, NOW(), NOW());

-- Create demand hotspots for testing v6 Demand→Supply Loop
-- Insert 12 demand check-ins at Nyanya 07:00, 10 at Berger 07:00, 8 at Kubwa etc to trigger "12 people want" CTA

-- Example demand_requests for testing (run after junctions seeded):
-- INSERT INTO demand_requests (user_id, pickup_lat, pickup_lng, destination_text, passengers_count, requested_at, status, junction_id) VALUES ...
