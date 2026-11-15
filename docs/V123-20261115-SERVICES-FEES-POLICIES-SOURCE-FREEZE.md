# V12.3 File08 — FR002 service visibility, fee range and policy disclosure root-cause freeze

Date: 2026-11-15 (Asia/Karachi). This is a **repository-only** source audit, not a claim of a live incident.

**Exact base:** PR53 HEAD `f388cbf09b2ec565a31be6bcb10555e9a54822c0`, which descends from main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. Candidate plugin header `1.2.16`; WCA schema declaration `3.4.0`. No deployed plugin checksum, active schema or current migration markers known. Jetpack connector `site_disconnected`; WPVibe `HTTP 530` is not a root-cause diagnosis.

## Scope frozen BEFORE correction
1. **V123-F08-SVC-01 (FR001/002/005/010, HIGH source correctness):** `WCA_Repository::list_services($clinic_id,$public_only=true,$doctor_user_id=0)` accesses undefined `$limit` when calculating `min(MAX_COLLECTION_ROWS,max(1,absint($limit)))`. The computed limit is effectively one under WordPress `absint` semantics, and PHP emits undefined-variable diagnostics. This truncates both public and private service listings, and `WCA_Service::public_clinic_projection` consumes the truncated list. Expected default is the existing `MAX_COLLECTION_ROWS=100` bound. Reproduction must invoke the real repository method with >1 fake rows and assert actual query `LIMIT 100`, no notices or partial results.
2. **V123-F08-SVC-02 (FR002, UX-04, NFR010, truthful fee information):** The public projection already carries `fee_minor`, `fee_max_minor`, per-service `tax_policy`, `refund_policy`, `cancellation_policy`, and clinic `policies`, but `WCA_Frontend::clinic` shows only the minimum fee and `booking` lists service name without fee/policy. Patients cannot inspect a variable fee range and its conditions at the point they request an appointment. Only safe public projection fields may be displayed, with HTML escaping. Never assert payment is completed or required by platform; 0% platform commission must be explicit.
3. **V123-F08-SVC-03 (FR002, NFR010):** The allowed consultation type `home_visit` is persisted by `WCA_Repository::save_service`, while frontend `consultation_type_label` maps only `online`, `in_person`, `hybrid`. A valid service is labelled “Consultation type unavailable.” Add a translated, accurate “Home visit” label.

## Correction limits
- A regression MUST fail before modification and pass after.
- Narrow code boundary: `includes/class-wca-repository.php`, `includes/class-wca-frontend.php`, necessary progressive accessibility of `assets/js/clinic.js`, isolated test fixtures, and test runner / workflow.
- Do not change price values, taxes, provider contracts, patient records, schema, commission, runtime version, active feature flags or Live.
- Source-level tests are **not** Staging E2E. Require exact-HEAD CI, independent review, host staging, backup/rollback and Founder approval before release.
- If service policies are not published, do not invent them. Show truthful absence and require the user to consult the clinic; correct the source policy gap rather than fabricating terms.

## Mandatory outcomes
Repository main HEAD / candidate exact SHA / source tests / deployed version / DB version / migration state / Live verification; Found defects / source-fixed / merged / staged / live-resolved counts.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
