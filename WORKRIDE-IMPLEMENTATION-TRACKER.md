# WorkRide — Implementation Tracker (Resume Record)

> **Purpose:** The single "continue from where you left off" record. If a session
> stalls, the next session reads ONLY this file to resume: current work package,
> exact status of every activity, the merged plan, and the next action.
> Companion to `DEVELOPMENT-LOG.md` (permanent history) and `WORKRIDE-ROADMAP.md` (gaps).
> Last updated: 2026-08-08 — Session: **v0.27.0 Driver Trip Templates + Demand Prompts**

---

## 0. HOW TO RESUME (read first)

1. Read the **Current Work Package** (§2) → do the item marked `▶ IN PROGRESS`.
2. Each package has a **gate** ("done when"). Run it before moving on.
3. After every change: run the DoD ritual (see §6) and update §2 status + `DEVELOPMENT-LOG.md`.
4. Commits: one per package, conventional messages, tag `v0.26.0` when the sprint ends (guide §19).

**Session stall protocol:** if this session is interrupted, the next session must
start at §2's `▶ IN PROGRESS` item — do not re-review gallery docs, do not re-plan.

---

## 1. Merged Plan Source Material (gallery_of_files/)

Five docs reviewed 2026-08-07. Repetitive/duplicate ideas removed during merge (below).

| Doc | What it proposes | Merge verdict |
|-----|------------------|---------------|
| `input section.txt` (1128 L) | Raw idea dump: soft_hold bookings, live driver screen, driver trip templates, corridor stats, offline cache, AR/voice (2028), localization, chat reliability | ADOPT (soft_hold, corridor stats, templates) · DEFER (AR/voice/localization/chat) · ALREADY-DONE (live driver screen, offline cache) |
| `WORKRIDE-PROMPT-REMAINING-TASKS-v6-MATCHING-POLISH-OFFLINE.md` (284 L) | Weighted matching score + reasons, fairness/rotation, driver prompts, offline board, templates | ADOPT (weighted score + reasons + supply CTA) · DEFER (rotation is cosmetic risk — see §1.1) |
| `WORKRIDE-PROMPT-SERVICE-PLANNING-LIVE-JOURNEY.md` (272 L) | Corridor service planning phases 0-6: junction catalogs, journey planning, live journey, service quality | MOSTLY-DONE (Sprint 3 progress tracker, connect guide, waypoints) · ADOPT (junction demand hotspots on board) |
| `OPENCODE_PROMPT_REBRAND NEW.md` (361 L) | Award-grade rebrand (design tokens, landing, PWA) | MOSTLY-DONE (v0.10/v0.12/v0.20 design passes) · nothing new to implement |
| `WORKRIDE-45-JUNCTIONS-SEED.sql` (78 L) | 45-junction seed incl. `garki_wuse` (4th corridor), `passenger_volume_daily`, `is_major_hub`, `state`, `avg_wait_time_mins` | ADJUSTED — 45 junctions already seeded (`JunctionSeeder`). New columns + 4th corridor DEFERRED (Corridor enum is 3 corridors; adding `garki_wuse` touches pricing/GTFS/filters — separate work package) |

### 1.1 Ideas REMOVED as repetitive / out-of-scope for this pass
- **Rotation/fairness demotion** (v6): demand is supply-constrained (few drivers); rotating results can hide the only real match. Instead we show **score + reasons** so riders understand the ranking; fairness is deferred until supply > 3x demand.
- **AR/voice/haptics 2028 ideas** (input/rebrand): already in `WORKRIDE-ROADMAP.md` P4.
- **Google OAuth, Paystack live keys, Termii/IdentityPass/Smile live** — P2 production wiring, not a feature; unchanged.
- **MapLibre premium tiles** — cost/complexity, DEFER.
- **Localization / USSD / multi-tenant cities** — P3/P4 roadmap rows, untouched.

### 1.2 What this pass actually ships (the merged, aligned scope)
Everything aligns with existing systems — no parallel tables, no money/booking-gate changes.

| # | Feature | Aligns with | Package |
|---|---------|-------------|---------|
| 1 | **Weighted matching score** (score 0-100 + reason chips) on board + API + match | `TripMatchingService` | P1 |
| 2 | **Demand hotspots** (junction counts + pending check-ins) on board map/empty state + **"be the driver"** supply CTA | `DemandService`, `trips/board`, `/go` | P2 |
| 3 | **Soft reservations** (`soft_hold` booking status, 3-min hold, `ReleaseExpiredSoftHoldsJob`) | `BookingService`, `BookingStatus`, existing idle-checking | P3 |
| 4 | Tracker + this plan + `DEVELOPMENT-LOG.md` update + tag `v0.26.0` | guide §19 | P4 |

Deferred to `v0.27.0` (tracked below, not this pass): driver trip templates + driver prompt notifications.

---

## 2. Current Work Package — v0.26.0 (session 2026-08-07)

> State legend: `[ ]` pending · `[~]` in progress · `[x]` done · `[!]` blocked
| Activity | Status | Gate ("done when") |
|----------|--------|--------------------|
| A. Fix `Unknown column schedule_ref` 500 on live MySQL | `[x]` | `php artisan migrate --force` applied 5 pending migrations; tinker query on `trips` runs without error |
| B. Create this tracker | `[x]` | this file exists, resume instructions readable |
| C. Review + merge gallery docs | `[x]` | §1 table above |
| D. **P1 Weighted matching score** | `[x]` | `TripMatchingService` returns scored trips (0-100 + `score_reasons`); board + API show score; tests green |
| E. **P2 Demand hotspots + supply CTA** | `[x]` | `DemandService::hotspots()`; board/`/go` empty state lists top junctions with counts + "Be the driver" publish link; tests green |
| F. **P3 Soft reservations** | `[x]` | `BookingStatus::SoftHold`; `BookingService::softHold()`; `ReleaseExpiredSoftHoldsJob` (feature-gated `FEATURE_SOFT_HOLD`); tests green |
| G. DoD + commit + tag | `[x]` | pint → phpstan → test → build all green; one commit per package; `v0.26.0` tag; `DEVELOPMENT-LOG.md` updated |

▶ **NEXT ACTION:** package v0.26.0 is complete — 548 tests / 1800 assertions green, DoD ritual run, `v0.26.0` tagged and pushed per guide §19. **v0.27.0 is also complete** (see §5.1 — 576 tests / 1886 assertions, tag `v0.27.0` pushed). Next session: work the **gallery-consolidated remaining-task list** (v6.0 matching/polish/offline + service-planning/live-journey + rebrand) per the session's implementation list.

---

## 3. Deferred Backlog (after v0.26.0)

| Next | Feature | Depends on | Notes |
|------|---------|-----------|-------|
| v0.27.0 | Driver trip templates + driver prompts | P1 (score feeds prompt relevance) | `trip_templates` table, reuse `PricingService`; notify driver "N people want corridor X" | ✅ DONE 2026-08-08 — see §5 + `DEVELOPMENT-LOG.md` §4.38 |
| v0.27.0 | `garki_wuse` 4th corridor | enum/pricing/GTFS/filter audit | Only if a 4th corridor is actually approved |
| later | Junction column upgrade (volume/hub/state/avg-wait) | — | Seed SQL exists in gallery; needs migration |
| P4 | AR/voice/haptics, insurance, union shares, FERMA MOU | — | roadmap P4 |

---

## 4. Environment Facts (for resuming)

- MySQL: Laragon 8.4.3, `127.0.0.1:3306`, db `workride`, root / no password. Logs `D:\Softwares\laragon\data\mysql-8.4\mysqld.log`.
- All migrations applied as of 2026-08-07 (scheduling + push migrations included).
- Git: branch `main`, ahead of `origin/main` by 2 commits (`4d9088c` FCM push, `6e142a1` schedules). Working-tree change: `tests/Feature/SchedulingTest.php` (intentional GtfsRoute factory edit — keep, don't revert).
- Commands: `php artisan test` · `vendor\bin\pint` · `vendor\bin\phpstan analyse` · `npm run build`.
- Env gates: `FEATURE_DEMAND` on (default), `FEATURE_SOFT_HOLD` new/off-default, all others unchanged.

---

## 5. Session Log (2026-08-07)

- Diagnosed 500 `Unknown column 'schedule_ref'`: migration `2026_08_07_120001_add_schedule_refs_to_trips_table.php` never applied to live MySQL (tests green on fresh SQLite masked it). Ran `php artisan migrate --force` — applied `notifications`, `bus_schedules`, `schedule_refs`, `device_tokens`, `arrival_notified_at`. Verified `Trip` query in tinker returns cleanly.
- Read all 5 gallery docs + `WORKRIDE-ROADMAP.md`; grounded in `TripMatchingService`, `DemandService`, `BookingService` (partial), `JunctionSeeder`, junctions migration, `Corridor`/`BookingStatus` enums.
- Wrote this tracker + the merged plan (§1).
- **P1 committed** `f15af52` — weighted score (proximity 40 / timing 25 / rating 15 / verification 10 / seat-fill 10 from `workride.matching.score_weights`) + readable reasons on board/API/live corridor chips; `scoreTrip()` feeds `upcoming()` ordering.
- **P2 committed** `b90879a` — `DemandService::hotspots()` fuses 24h junction counts + pending check-ins (1 km attribution) into per-junction tallies; board strip + `/trips` + `/go` empty states list top junctions; Level 1+ get "Be the driver" CTA (pre-selects corridor), phone-only riders see a wait message.
- **P3 (this session)** — soft reservations end-to-end: `BookingStatus::SoftHold` + `soft_hold_expires_at` migration, `BookingService::softHold()` (mirrors `book()`'s atomic lock/hold/employer coverage/seat decrement, ride-credit excluded, 3-min `ttl_minutes` hold), `confirmSoftHold()` (under row lock; expired → error), `releaseExpiredSoftHolds()` + `ReleaseExpiredSoftHoldsJob` (every minute, refund via `WalletService::releaseHold`, seat back, interest revert, live `TripSeatsUpdated`); web + API controllers/routes, hold form on `trips/show`, confirm/countdown in My Rides; feature-gated `FEATURE_SOFT_HOLD` (off by default); 15 tests.
- DoD for P3: `pint --test` clean · PHPStan L8 green (baseline regenerated; controller return types + `?->canBook()` fixed in code) · `npm run build` clean · **548 tests / 1800 assertions green** · `migrate:fresh --seed` ~62s · `gtfs:generate` valid (171 stops, 3 routes, 32 trips).
- Committed P1/P2/P3 + tag `v0.26.0`, pushed per guide §19. `DEVELOPMENT-LOG.md` updated with §4.37 + version-history row.

## 5.1 Session Log (2026-08-08) — v0.27.0

- Implemented the §3 deferred v0.27.0 package: **driver trip templates** (guide §11 driver tooling) + **demand-driven driver prompts** (gallery "service planning" Phase 3).
- Templates: `trip_templates` table + `TripTemplate` model (`nextDeparture` today-or-tomorrow, `runsOn`, `daysLabel`, `routeTitle`, `markUsed`, `HasFactory`), `TripTemplateService` (`store`/`forDriver`/`saveFromTrip`/"save this commute"/`publish` one-tap/`publishWeek` repeat-group/`assertOwner`), `TripTemplateController` (index/store/publish/publish-week/destroy), rider `templates/index` page, "Saved commutes" chips + save checkbox on `trips/create`, profile-menu link. Gated `FEATURE_TRIP_TEMPLATES` (on by default).
- Prompts: `driver_prompts` table (unique `PROMPT-{driver}-{Ymd}-{corridor}` reference = 1-push/day/corridor), `DriverPrompt` model (`accept`/`dismiss`), `DriverPromptService` (`demandForCorridor` nearest-junction attribution, `supplyForCorridor`, `triggersFor` demand ≥ min AND supply < demand/divisor, `qualifiedDrivers` affinity-first, `promptForCorridor` gated on triggers, `nudgeAll`, `activeFor`), `CalculateDriverPromptsJob` (every 30 min), `DriverPromptController` accept/dismiss, board "Demand wants you" panel, Control Tower `admin.ops.nudge`. Gated `FEATURE_DRIVER_PROMPTS` (off by default). `TripService::publish` gained `?int $repeatHorizonDays`.
- **5 hardening bugs fixed in tests:** `nextDeparture` narrowed to today-or-tomorrow (Sat-only on Monday correctly rejected) · `assertOwner` on publish/publishWeek · publishWeek horizon capped `min(horizon_days, 7-dayOfWeek)` (Monday → Mon-Fri, never bleeds next week) · `promptForCorridor` gated on `triggersFor()` (supply-covers-demand no-op) · test `setTestNow` moved before trip build.
- DoD: `pint --test` clean · PHPStan L8 green (baseline +156 entries, Eloquent-inference classes only) · `npm run build` clean · **576 tests / 1886 assertions green**.
- Committed `656423f` + tag `v0.27.0`, pushed to GitHub per guide §19. `DEVELOPMENT-LOG.md` §4.38 + version-history row updated.
