# AI CODER PROMPT: Government ID Scan + 3D Selfie Liveness — SECURE 3-TIER HYBRID MODEL
### World-Class, Fundable, NDPR-Compliant, Anti-Spoofing Ready

> **Feature Name:** Tiered Identity Verification — Staff ID (Open) + NIN (Hybrid with NIMC) + Driver (Commercial Anti-Spoofing)
> **Priority:** CRITICAL — Sprint 3.6 — Blocks driver onboarding, wallet withdrawals, MDA contracts
> **Goal:** Bank-grade KYC that works on low-end Android, $0 for 80% users, ₦100 for NIN, $0.05 for drivers, prevents tablet-video spoofing
> **Compliance:** NDPR, NIMC, CBN Tiered KYC, ISO 27001, FCTA Transport Sec requirements
> **Business Impact:** Fake driver = lawsuit, fake NIN = subsidy fraud. This tiered model is same as PalmPay/Moniepoint — fundable as financial inclusion

---

## ROLE
You are a Senior Computer Vision Engineer + Laravel Security Lead + NIMC Integration Specialist. You have built KYC for PalmPay, Moniepoint, Smile Identity, IdentityPass. You know: MediaPipe liveness limits, anti-spoofing depth checks, NIMC licensed API flow, NDPR NIN hashing, and how to fail safely to manual review.

## CRITICAL SECURITY TRUTH — READ BEFORE CODING:

**Issue 1 — Liveness Spoofing:**
Open-source MediaPipe + face-api.js CAN catch printed photo (no blink) but CANNOT reliably catch high-res tablet video replay or 3D mask. It lacks depth map, corneal reflection, rPPG blood flow analysis. Commercial providers (Smile Identity) train on 10M+ spoof samples.

**Issue 2 — Government Database Access:**
Open-source Tesseract can READ NIN number from slip image, but CANNOT verify if NIN exists in NIMC registry. Legal access to NIMC requires NIMC-licensed partner (IdentityPass/Prembly, Dojah, VerifyMe, Smile Identity) who pay NIMC ₦400/check. You must call their API.

**Solution — 3-Tier Hybrid (Open-Core + Paid-Facade):**
- Tier 1 Low Risk (Passengers, Staff ID): Open source $0 — fast UX, inclusive
- Tier 2 Medium Risk (NIN, Subsidy): Hybrid ₦100 — open read + commercial NIMC verification
- Tier 3 High Risk (Drivers, Withdrawals >₦10k): Commercial $0.05 — Smile Identity SmartSelfie with true anti-spoofing AI

This is exactly how Moniepoint does it.

---

## TECH STACK:

**Frontend PWA (All Tiers):**
- MediaPipe Face Mesh 0.4 (468 landmarks), face-api.js 0.22 (128D descriptor), OpenCV.js 4.8 (doc detect + perspective correction), Tesseract.js 5 (OCR)
- Alpine.js component `idVerification()`, Tailwind glassmorphism UI

**Backend Laravel 11:**
- Services: `LivenessVerificationService`, `NimcVerificationService`, `SmileIdService`
- Python microservice optional: `ml/face_match` FastAPI + DeepFace 0.0.79 for face match fallback
- PostgreSQL + encrypted disk `storage/app/private/verifications`

**Commercial APIs (Env toggled, capped):**
- **Tier 2:** IdentityPass (Prembly) or Dojah — `IDENTITYPASS_API_KEY`, `DOJAH_API_KEY` — NIN verification endpoint
- **Tier 3:** Smile Identity — `SMILE_PARTNER_ID`, `SMILE_API_KEY`, `SMILE_SID_SERVER` — SmartSelfie Authentication + Doc Verification

**Cost Logging:**
- Table `api_cost_logs`: id, provider (identitypass/smile), purpose (nin_check/driver_liveness), cost_ngn, user_id, reference, created_at

---

## IMPLEMENTATION — 3 TIERS:

### TIER 1: Staff ID / Workplace ID — Level 1 Verification (Open Source, $0, 80% users)

**Use Case:** Passenger wants to book ride, has Federal Secretariat staff ID. Low risk — worst case free ride.

**Frontend `resources/views/verify/tier1.blade.php`:**
- OpenCV.js: Detect ID card rectangle, auto-capture when sharpness (Laplacian variance >100), perspective correction `cv.getPerspectiveTransform`
- Tesseract.js: `Tesseract.recognize(cropped, 'eng')` → extract Staff ID number via regex
- MediaPipe: Challenges: Blink (Eye Aspect Ratio <0.2), Turn Left (yaw >20°), Turn Right (yaw <-20°), Smile (mouth ratio >2). Score 25 per challenge, need 75+
- Anti-spoof basic: Check single face, face size >15% frame, texture variance > threshold (prevents screen photo)
- POST `/api/v1/verifications/tier1` with {document_type=staff_id, document_number_hash=sha256(number), document_last4, selfie_base64, liveness_score, face_descriptor}

**Backend `LivenessVerificationService::verifyTier1`:**
- Validate liveness_score >=75, face_descriptor present
- Face match: Compare selfie vs staff ID photo via face-api.js descriptor Euclidean distance <0.6 OR DeepFace `DeepFace.verify` distance <0.4
- If pass → Create Verification type=workplace, status=approved, verification_level=1, selfie_encrypted_path = encrypt and store, liveness_score
- Rate limit: 5 attempts/day, log to `verification_attempts`

### TIER 2: NIN Verification — Level 2 Verification (Hybrid, ₦100/check, MDA pays)

**Use Case:** User wants subsidy credits or to send >₦5k P2P. Need government database confirmation.

**Frontend:** Same as Tier 1 but document_type=nin_slip. Tesseract reads 11-digit NIN. Hash NIN in frontend via SubtleCrypto SHA256, send ONLY hash + last4 + selfie + liveness_score. Never send raw NIN image to your server for NIN (NDPR).

**Backend `NimcVerificationService::verifyNin`:**
```php
// Step 1: Call IdentityPass/Dojah NIN verification
$response = Http::withToken(config('services.identitypass.key'))
  ->post('https://api.myidentitypass.com/api/v2/biometrics/merchant/data/verification/nin', [
    'number' => $nin // Use real NIN here, not hash, because licensed partner encrypts it legally
  ]);
// Response: {status: true, data: {firstname, lastname, dob, photo (base64 official NIMC photo)}}

if (!$response['status']) → verification status=rejected, reason=nin_not_found

// Step 2: Face match official NIMC photo vs selfie
$officialPhoto = base64_decode($response['data']['photo']);
$selfie = base64_decode($data['selfie_base64']);
$distance = DeepFace::verify($officialPhoto, $selfie); // or face-api.js

if distance <0.4 && liveness_score >=75 → approved Level 2
else → rejected

// Step 3: Store only hash, not raw NIN. Log cost.
ApiCostLog::create(['provider'=>'identitypass','cost_ngn'=>100,'user_id'=>$user->id]);
Verification::create(['type'=>'nin','document_hash'=>sha256($nin),'document_last4'=>last4,'liveness_score'=>$score,'status'=>'approved']);
```

**Env:** `USE_IDENTITYPASS=true`, `IDENTITYPASS_COST_CAP_NGN=50000` (monthly). If cap reached or API down → mark verification as `pending_manual_review`, notify admin in Control Tower.

**Security:** IdentityPass/Dojah handle NIMC encryption legally. You never store raw NIN, only hash. Official NIMC photo deleted after verification (store only embedding hash).

### TIER 3: Driver / High Value — Level 3 Verification (Commercial Anti-Spoofing, $0.05/check)

**Use Case:** User wants to drive and carry passengers or withdraw earned_balance >₦10k. High risk — fake driver = accident, lawsuit.

**Frontend:** Use Smile Identity Web SDK v6 (https://docs.usesmileid.com/web) — drop-in widget that does true anti-spoofing:
- Detects depth, screen moire, reflection, rPPG
- Challenges: blink, turn, smile with active guidance
- Returns `job_id`

**Backend `SmileIdService::verifyDriver`:**
```php
// Step 1: Create Smile job
$job = Http::withBasicAuth(config('services.smile.partner_id'), config('services.smile.api_key'))
  ->post('https://api.usesmileid.com/v1/smile_jobs', [
    'partner_id' => config('services.smile.partner_id'),
    'job_type' => 1, // SmartSelfie Authentication
    'user_id' => $user->id,
    'images' => ['selfie' => $selfie_base64, 'id_card' => $id_card_base64]
  ]);

// Poll result or webhook
$result = $this->pollJob($job['job_id']); // result['Result']['ResultCode'] 0=pass, anti-spoofing score

if ResultCode == 0 && anti_spoofing_score >80 && face_match >70 → Level 3 approved

// Also verify driver license via Smile doc verification job_type 6
```

**Fallback:** If Smile down, require manual admin verification with video call + document check, mark as `pending_manual_review`.

**Cost:** $0.05 per driver verification. Charge driver ₦500 verification fee (covers cost + profit). Only 100 drivers = $5.

---

## DATABASE MIGRATIONS:

```php
// *_add_liveness_to_verifications.php
Schema::table('verifications', function($table){
  $table->integer('liveness_score')->nullable();
  $table->string('face_embedding_hash')->nullable();
  $table->string('selfie_encrypted_path')->nullable();
  $table->integer('attempt_count')->default(0);
  $table->string('provider')->default('open'); // open, identitypass, smile
  $table->string('nimc_reference')->nullable();
  $table->enum('tier', ['1','2','3']);
});

// *_create_verification_attempts_table.php
id, user_id FK, tier ENUM, provider, liveness_score, face_match_score, status, ip_address, created_at

// *_create_api_cost_logs_table.php (already planned)
id, provider ENUM(identitypass,dojah,smile), purpose, cost_ngn decimal, cost_usd decimal, user_id FK, reference unique, created_at
```

## API ENDPOINTS:

- `POST /api/v1/verifications/tier1` — Staff ID open
- `POST /api/v1/verifications/tier2` — NIN hybrid with IdentityPass
- `POST /api/v1/verifications/tier3` — Driver Smile
- `GET /api/v1/verifications/status` — my verification status
- Webhook `POST /api/v1/webhooks/smile` — Smile result callback

## ADMIN CONTROL TOWER — `/admin/verifications`:

- Table columns: User, Tier, Provider, Liveness Score badge (Green >=80, Yellow 75-79, Red <75), Face Match %, Status, Cost, Actions
- View encrypted selfie (decrypt on-the-fly)
- Buttons: Approve, Reject, Request Smile re-check, View NIMC official photo (Tier2)
- Cost dashboard: Total spent this month, cap warning

## TESTS (Must Pass):

```
test_tier1_staff_id_open_liveness_passes_with_blink_and_yaw
test_tier1_rejects_tablet_video_with_low_texture_variance
test_tier2_nin_calls_identitypass_and_matches_official_photo
test_tier2_fails_when_nin_not_found_in_nimc
test_tier2_fallback_to_manual_review_when_api_down
test_tier3_driver_requires_smile_anti_spoofing_score_80
test_raw_nin_never_stored_only_hash
test_rate_limit_5_attempts_per_day
test_api_cost_logs_created_for_every_commercial_call
test_encrypted_selfie_auto_deleted_after_30_days
```

## DELIVERABLES:

- `resources/views/verify/tier1.blade.php`, `tier2.blade.php`, `tier3.blade.php` + `resources/js/verification/liveness.js` (MediaPipe + OpenCV + Tesseract + Smile SDK)
- `app/Services/LivenessVerificationService.php`, `NimcVerificationService.php`, `SmileIdService.php`
- `app/Http/Controllers/Api/V1/VerificationController.php` (tier1, tier2, tier3 methods)
- Migrations, Models, `ml/face_match` FastAPI microservice (optional)
- `docker-compose.yml` add `face_match` service
- `.env.example` add `IDENTITYPASS_API_KEY=`, `SMILE_PARTNER_ID=`, `VERIFICATION_MODE=open_hybrid`, `MONTHLY_NIN_CAP_NGN=50000`
- Update `DEVELOPMENT-LOG.md` Sprint 3.6

## SUCCESS CRITERIA:

- Low-end Android Chrome captures ID + passes liveness in <15 seconds on 3G
- Tier 1 cost $0, Tier 2 ₦100 (MDA pays), Tier 3 $0.05 (driver pays ₦500)
- Tablet video replay attack fails (liveness <75 or Smile anti-spoofing fails)
- Fake NIN fails (IdentityPass returns not found)
- Raw NIN never stored, only SHA256 hash
- Admin can see cost and cap

Build this — this is bank-grade, fundable, and honest about open-source limits.

