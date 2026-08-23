# WorkRide — GTFS → Google Transit Submission Guide

> **Goal:** Get Abuja's first public transit feed live on Google Maps.
> **Current state:** Static GTFS + GTFS-RT generating at `/gtfs/gtfs.zip` and `/gtfs/gtfs-rt/vehicle_positions.pb`.
> **Target:** Approved by Google Transit Partner Program, searchable on Google Maps as "WorkRide Staff Bus".

---

## 1. Feed Endpoints (Production URLs)

| Feed | URL | Format | Update Frequency |
|------|-----|--------|------------------|
| Static GTFS | `https://workride.ng/gtfs/gtfs.zip` | ZIP (7 files) | Nightly + on trip publish |
| GTFS-RT Vehicle Positions | `https://workride.ng/gtfs/gtfs-rt/vehicle_positions.pb` | Protobuf | Real-time (30s) |
| GTFS-RT Trip Updates | `https://workride.ng/gtfs/gtfs-rt/trip_updates.pb` | Protobuf | Real-time (30s) |

> **Note:** Replace `workride.ng` with your actual production domain.

---

## 2. Pre-Submission Checklist

Run these validations **before** submitting:

```bash
# 1. Generate fresh feed
php artisan gtfs:generate

# 2. Validate locally (optional - uses feedvalidator.mobilitydata.org)
#    Upload gtfs.zip to https://feedvalidator.mobilitydata.org/

# 3. Check required fields
#    - agency.txt: agency_id, agency_name, agency_url, agency_timezone, agency_lang
#    - stops.txt: stop_id, stop_name, stop_lat, stop_lon
#    - routes.txt: route_id, agency_id, route_short_name, route_long_name, route_type=3
#    - trips.txt: route_id, service_id, trip_id, trip_headsign, shape_id
#    - stop_times.txt: trip_id, arrival_time, departure_time, stop_id, stop_sequence
#    - calendar.txt: service_id, monday-sunday, start_date, end_date
#    - shapes.txt: shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence

# 4. Verify GTFS-RT endpoints return valid protobuf
curl -I https://workride.ng/gtfs/gtfs-rt/vehicle_positions.pb
# Should return: content-type: application/octet-stream (or application/x-protobuf)
```

### Key Validation Points

| Check | Expected | Current Status |
|-------|----------|----------------|
| All 7 files present | ✅ | ✅ agency, stops, routes, trips, stop_times, calendar, shapes |
| Valid coordinates (WGS84) | ✅ | ✅ Abuja bounds 8.6–9.4, 6.9–7.7 |
| `route_type=3` (bus) | ✅ | ✅ All 3 routes |
| `service_days` covers 365 days | ✅ | ✅ 2026-08-23 to 2027-08-23 |
| Stop times interpolated at 30 km/h | ✅ | ✅ Configured |
| GTFS-RT vehicle positions | ✅ | ✅ Returns active trips only |
| GTFS-RT trip updates | ✅ | ✅ Empty snapshot (no active trips) |

---

## 3. Feed Validator

**Online validator:** https://feedvalidator.mobilitydata.org/

1. Go to the validator
2. Upload `storage/app/public/gtfs/gtfs.zip`
3. Review warnings/errors:
   - **Errors** = must fix before submission
   - **Warnings** = review but may be acceptable

### Common Expected Warnings (Acceptable)

| Warning | Reason | Action |
|---------|--------|--------|
| `arrival_time` / `departure_time` > 24:00:00 | Trips spanning midnight (e.g., 23:51 → 00:42) | GTFS spec allows this; keep as-is |
| `stop_times` without `stop_headsign` | Not required for bus routes | Ignore |
| `shapes` not covering all trip paths | Synthetic stops use straight-line shapes | Acceptable for informal corridors |

---

## 4. Google Transit Partner Program Submission

### Step 1: Prepare Production Domain

Ensure these are configured in `.env`:

```env
APP_URL=https://workride.ng
WORKRIDE_GTFS_AGENCY_URL=https://workride.ng
WORKRIDE_GTFS_AGENCY_NAME="WorkRide Staff Mobility"
WORKRIDE_GTFS_AGENCY_ID=WR
```

### Step 2: Apply to Google Transit Partner Program

1. Go to: https://transitpartnerprogram.withgoogle.com/
2. Click **"Get started"** → **"Add a new feed"**
3. Fill in:
   - **Feed name:** `WorkRide Abuja Staff Mobility`
   - **Feed format:** `GTFS`
   - **Feed URL:** `https://workride.ng/gtfs/gtfs.zip`
   - **Feed type:** `Static`
   - **Entity type:** `Transit agency`
   - **Agency name:** `WorkRide Staff Mobility`
   - **Agency URL:** `https://workride.ng`
   - **Agency timezone:** `Africa/Lagos`
   - **Agency language:** `en`
   - **Contact email:** `gtfs@workride.ng` (create this alias)

4. For **Real-time feed** (optional but recommended):
   - Add another feed entry
   - **Feed type:** `GTFS-Realtime`
   - **Feed URL:** `https://workride.ng/gtfs/gtfs-rt/vehicle_positions.pb`
   - **Entity type:** `Vehicle positions`

### Step 3: Google Review Process

- Google validates the feed (automated + manual)
- Typical timeline: **2–10 business days**
- You'll receive email with:
  - ✅ **Approved** → Feed goes live on Google Maps
  - ❌ **Rejected** → Fix errors, resubmit
  - ⚠️ **Needs changes** → Address warnings, resubmit

### Step 4: Post-Approval

Once approved:
1. Feed appears on Google Maps within 24–48 hours
2. Search "Kubwa to CBD" → shows "WorkRide Staff Bus 2 — 6:45 AM"
3. Monitor feed health at https://transitpartnerprogram.withgoogle.com/dashboard

---

## 5. Maintenance & Monitoring

### Automated Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `gtfs:generate` | Nightly 02:00 | Regenerate static feed |
| `GenerateGtfsFeedJob` | On trip publish | Incremental update |

### Health Checks (add to monitoring)

```bash
# Static feed accessibility
curl -f https://workride.ng/gtfs/gtfs.zip

# GTFS-RT vehicle positions
curl -f https://workride.ng/gtfs/gtfs-rt/vehicle_positions.pb

# GTFS-RT trip updates
curl -f https://workride.ng/gtfs/gtfs-rt/trip_updates.pb
```

### Alerting Thresholds

| Metric | Warning | Critical |
|--------|---------|----------|
| Feed size < 1 KB | ❌ | ✅ |
| Vehicle positions empty for > 1 hr (peak) | ✅ | ❌ |
| Feed not updated for > 48 hrs | ✅ | ❌ |
| Validator new errors | ✅ | ❌ |

---

## 6. Current Feed Statistics (v0.30.0)

| Metric | Value |
|--------|-------|
| Feed size | 4.6 KB |
| Stops | 171 (53 catalog + 118 synthetic) |
| Routes | 3 (KUB-CBD, NYY-IDU, LUG-CBD) |
| Trips | 32 (across 3 routes) |
| Service period | 2026-08-23 to 2027-08-23 |
| GTFS-RT vehicle positions | 583 bytes (active trips) |
| GTFS-RT trip updates | 13 bytes (empty snapshot) |

---

## 7. Troubleshooting

### Feed not updating
```bash
# Check job queue
php artisan queue:failed

# Manual regenerate
php artisan gtfs:generate

# Check logs
tail -f storage/logs/laravel.log | grep -i gtfs
```

### GTFS-RT empty when trips are active
```bash
# Verify active trips exist
php artisan tinker --execute="App\Models\Trip::where('status', 'active')->count()"

# Check GtfsRtService logs
grep -i "GtfsRtService" storage/logs/laravel.log
```

### Validator errors: "Stop not found in shapes"
- Increase `WORKRIDE_GTFS_STOP_MATCH_RADIUS_M` (default 1500m)
- Or add missing stops to `gtfs_stops` table

---

## 8. Next Steps After Approval

1. **Add GTFS-RT Trip Updates** with real delay/cancel data
2. **Add `frequencies.txt`** for headway-based routes (recurring schedules)
3. **Add `transfers.txt`** for corridor interchanges (Kubwa ↔ Nyanya via Berger)
4. **Multi-city feeds** when expanding to Lagos/Nairobi (separate feed per city)

---

## 9. References

- GTFS Spec: https://gtfs.org/schedule/reference/
- GTFS-Realtime Spec: https://gtfs.org/realtime/reference/
- Google Transit Partner Program: https://transitpartnerprogram.withgoogle.com/
- Feed Validator: https://feedvalidator.mobilitydata.org/
- MobilityData GTFS Best Practices: https://github.com/MobilityData/gtfs-best-practices

---

*Last updated: 2026-08-23 — Feed ready for validation and submission*