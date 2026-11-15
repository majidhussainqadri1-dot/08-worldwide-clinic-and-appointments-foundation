# File08 V12.3 — Exact-HEAD Cross-Owner Contract Freeze (2026-11-15)

**Review scope:** File08 plan P08 and F08-CEN-01/02, plus File08 FR/UX-04, V12.3 modern UI/private-data rules. This is source owner reconnaissance, NOT owner sign-off, Staging or Live QA. Repository snapshots are independently resolved main HEADs and companion READMEs; any pending PR/branch must be checked separately.

## Exact companion SOURCE HEAD and canonical boundaries

| Native owner | Repository main SHA | Findings / File08 release gate |
|---|---|---|
| File14 global clinic CTA | `d1cd343392d07607c60f0688362cee450e31774a` | File14 README reports issue #70: File08 private/consented booking aggregates **unavailable**, never infer a zero or successful booking. File14 cannot substitute a navigation destination or auth grant via filters. Need native cross-owner negative/consent tests before enabling metrics/CTA |
| File17 communication/rooms | `8ae656e51796d1f05865d8be5dca2480443d79ca` | Owns private messaging, live communication and room signaling. File08 may only request owner-approved short-lived room provisioning; no recording consent assumptions |
| File19 notifications | `3cd8f01a4614e96c0104d0ff82faa4ad3f02f4e4` | Owns notification orchestration, preference and delivery; File08 appointments remain source-of-truth. Tests: cancel/decline suppress queued reminders, idempotent replay, provider outage/drain; actual providers and Staging unverified |
| File20 navigation shell | `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` | Owns global menus, slots, RTL shell and safe recovery. File08 may style its embedded content but cannot fork File20's structural navigation or claim Hostinger visual parity |
| File25 design/public UI | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` | Its README reports profile Appointment CTA hidden until a reviewed File08 doctor/profile-to-booking destination owner contract exists. An old SHA and File08 version in that README are historical and NOT current source proof. Real cross-owner destination+eligibility tests pending |
| File26 discovery/ranking | `1b064c6ea9f81195611ed001d5457e8ced65460a` | Search owner accepts **public approved clinic projections only**. No patient slots/records/fees private details, no donor/paid ranking. File08 PR #44 is draft and needs File26 owner acceptance and private-field negative tests |
| CF01 clinical charts | `c8fa142ee88d97174ad3f4514d062fe68b82a206` | CF01 README explicitly states disabled-by-default clinical record activation; patient use forbidden until external approvals. An appointment must NOT authorize chart access or activate CF01 unilaterally |
| CF03 financial operations | `24a8bbff7fd1940191ac947e4a23455d0567a0a3` | Financial domain owns verified transactions; File08 forwards fee snapshots/status and maintains **0% platform clinic commission**. No payment marked successful from an unverified webhook or source proxy; real provider/PCI/staging gates separate |

## Required real cross-owner scenarios (currently blocked / unverified)

1. Verified doctor with active published clinic and service: File07/26 public discovery → File25 / File14 approved CTA → File08 service/date/timezone request. Verify **no disclosure** of other patients' slots, private clinic notes, charts or payment tokens. Refuse suspended/unverified/guardian-expired owners; no URL/cached claim bypass.
2. Two patients hold the last slot concurrently; exactly one canonical hold; expiry, remote consent, replay, doctor approval and idempotent File19 event delivery must match File08 status. F08-FR-003–010 and NFR-001–004.
3. Clinic cancellation/doctor disruption while a File17 room link and File19 reminder are queued: owner events rechecked at action time, no stale reminder/room, no implicit recording, safe replay and manual restoration.
4. CF01 disabled: File08 booking and follow-up journey must remain functional in appointment scope while chart read/write fails closed. Ensure no public Search / File17 leakage of clinical notes.
5. CF03 payment/status interruption: no false paid or confirmed status, correct currency precision/fee range/0% platform commission, webhook signature/replay check, rollbacks, provider idempotency; no donation-derived doctor visibility.
6. File20/25 mobile Urdu/Arabic RTL 320/390/768/1024/1440 widths, accessibility keyboard/zoom/screen reader and poor connections. Real browser/Hostinger staging only.

## Current evidence classification

- **SRC:** companion repository heads and README ownership statements checked. This is not full current code-level semantic parity, because other owner branches and release contracts may have moved.
- **CI:** File08 Draft PR54 exact-source CI tested disposable File00/02/03/07/09/08 on 3 WP versions. **None of File14/17/19/20/25/26/CF01/CF03** are installed in the current six-plugin matrix.
- **STAGING:** no approved cross-owner Hostinger staging receipt.
- **LIVE:** active installed File08 code+checksum, DB, schema, migration flags and provider health unknown; Jetpack reports `site_disconnected`, WPVibe HTTP 530 (root cause unknown).

**Decision:** P08 source ownership reconnaissance done; **P08 operational acceptance is BLOCKED**. Do not silently substitute code found in other repositories for installed owner contract truth. No PR merge, provider activation, live payment, clinical chart activation or Live-Resolved claim.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
