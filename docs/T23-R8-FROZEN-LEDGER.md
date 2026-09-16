# T23 R8 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `54e8183b2e7cc46e6d2702e471650ff6da004527` (T23 R7 corrected exact head; canonical run `34901055065` completed successfully).

## Mandatory discipline

R8 was completed as a full read-only secure-continuity / CF01 / clinical-safety review before this ledger was created. No R8 correction was applied while the review was open.

## Review scope

Pre-visit intake authorization, current patient/guardian verification, emergency diversion, consent gating, optimistic versioning, encrypted persistence, follow-up creation/completion, File17 context minimization, continuity privacy exporter/eraser replacement, legal-hold monotonicity, bounded erasure, and separation of File08 continuity ownership from diagnosis/prescription/transport ownership.

## Frozen defects

### R8-D1 — replacement continuity eraser can delete patient continuity rows despite native appointment legal hold

`WCA_Continuity_Guards::replace_continuity_eraser()` replaces the canonical continuity eraser. Its patient/follow-up erasure loop computes hold state only through `apply_filters( 'wca_continuity_legal_hold', false, ... )`; it does not seed the filter with `WCA_Privacy::legal_hold( appointment_id )` and therefore does not preserve the monotonic native appointment legal hold that `WCA_Continuity::legal_hold()` already enforces. A row linked to a held appointment can therefore be deleted unless an external filter separately reasserts the hold.

### R8-D2 — replacement guardian anonymization bypasses legal holds and bounded traversal

The replacement eraser handles guardian references only on page 1 with one bulk `UPDATE ... WHERE guardian_user_id=<user>`. That path neither checks native/continuity legal hold per row nor uses a bounded cursor. It can anonymize guardian identity on records that must remain held, and its unbounded write contradicts the bounded-erasure discipline already implemented by the canonical continuity eraser.

## Reviewed non-defects

- Intake writes recheck appointment access, patient/current guardian authority, terminal state, emergency red flags and appointment-processing consent.
- Existing intake records require exact optimistic version before mutation.
- Follow-up creation is restricted to completed appointments, authorized treating/delegated clinical actors and active follow-up consent.
- File17 receives relationship/consent context but no clinical-record access or public-social context.
- Clinical continuity payloads are encrypted before persistence; File08 still does not own diagnosis/prescription or transport.

## Correction gate

After this ledger freeze only:
1. Make the replacement eraser use a monotonic legal-hold helper seeded from `WCA_Privacy::legal_hold( appointment_id )` for intake/follow-up deletion.
2. Replace the page-1 guardian bulk update with bounded cursor traversal that checks the same monotonic legal hold row by row and advances safely past held rows.
3. Add a permanent regression proving native hold preservation and bounded guardian traversal; bind it into `tests/run-all.php`.
4. Run exact-head canonical PHP 7.4/PHP 8.3, full source/JS/hygiene and reproducible candidate-package verification before R9 begins.

## Evidence boundary

Repository/source candidate evidence only. Actual production continuity rows, legal holds, encryption keys, privacy requests and live CF01 behavior remain separate evidence domains.
