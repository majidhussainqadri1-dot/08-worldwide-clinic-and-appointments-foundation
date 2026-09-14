# T23 R7 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `8687ea0f479b5fd7e11c50008e1a02177a674b50` (T23 R6 correction head; canonical run `34892064749` completed successfully).

## Mandatory discipline

R7 was completed as a full read-only post-correction integration review before this ledger was created. No R7 correction was applied while the review was open.

## Review scope

Fresh verification of the entire R6 privacy/retention correction as actually wired into runtime and permanent QA: plugin loader ordering, canonical boot order, WordPress `admin_init` callback replacement, legacy eraser lifecycle separation, normalized retention-policy ownership, stale-key removal, legal-hold preservation, source-suite aggregation and exact-head package behavior.

## Frozen defects

### R7-D1 — retention-policy normalizer is dead code at runtime

R6 added `includes/class-wca-retention-policy.php`, but the canonical loader in `worldwide-clinic.php` does not require that file and the runtime boot path never invokes `WCA_Retention_Policy::boot()`. `WCA_Plugin::boot()` still registers `WCA_Privacy::register_policy()` on `admin_init`, so the unsupported fixed appointment/event day defaults can continue to be seeded. The R6-D2 source correction therefore exists but is not active runtime behavior.

### R7-D2 — R6 corrections are not protected by a permanent canonical regression

`tests/run-all.php` contains no T23 R6 regression, and the current test inventory contains no T23 R6 test file. Consequently the exact failure above was able to pass PHP 7.4/PHP 8.3 source contracts and candidate packaging. The correction must be bound into the permanent aggregate suite.

## Reviewed non-defects

- `SWC_Privacy` no longer directly writes `_swc_status` during erasure; that part of R6-D1 is correctly repaired.
- `WCA_Retention_Policy::normalize()` itself removes the unsupported legacy appointment/event day keys, preserves enforced operational windows, forces external clinical/audit retention ownership markers and keeps legal-hold semantics monotonic.
- No destructive retention of appointments/events was introduced.

## Correction gate

After this ledger freeze only:
1. Load `class-wca-retention-policy.php` in the canonical loader.
2. Invoke `WCA_Retention_Policy::boot()` only after `WCA_Plugin::boot()` has registered the legacy callback, so the replacement can remove it reliably.
3. Add a permanent regression proving class loading, boot ordering, callback replacement, unsupported-key removal and the legacy eraser's non-lifecycle behavior; bind it into `tests/run-all.php`.
4. Run exact-head canonical PHP 7.4/PHP 8.3, full source/JS/hygiene and reproducible candidate-package verification before R8 begins.

## Evidence boundary

Repository/source candidate evidence only. Production option contents, production `admin_init` execution, actual legal holds, deployed schema and live behavior remain separate evidence domains.
