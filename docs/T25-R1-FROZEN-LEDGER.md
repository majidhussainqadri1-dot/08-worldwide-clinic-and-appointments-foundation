# T25 R1 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `8e3945b8b79afdf5ba5feb2d8c3222da0d86c225` (T24 R9 exact-head canonical run `35053963450` successful: PHP 7.4, PHP 8.3 and reproducible independently verified candidate package).

## Mandatory discipline

T25 R1 was completed as a full read-only governing-plan / brand / release-surface review before this ledger was created. No R1 correction was applied while the review remained open.

## Review scope

File 08 governing hierarchy and the 7 August central addendum; current Sabri Green `#087A4E` primary fallback with File 25 as visual-token owner; File 20 single-shell ownership; plugin/public/admin styling; RTL/reduced-motion/forced-colors; WordPress plugin readme release identity; current-vs-historical review evidence; staging/live evidence separation; central-governance manifest; and existing visual/governance regressions.

## Frozen defects

### R1-D1 — Packaged WordPress `readme.txt` still advertises T21 as the current repository review identity

The installable candidate includes `readme.txt`, but its Description and current 1.2.15 changelog still describe the resumed T21 cycle as current. README/STATUS/release-status/CHANGELOG were already advanced to T24. This creates release-truth disagreement inside the exact candidate payload and can misstate the provenance of a package even though exact-head CI/package identity is later.

### R1-D2 — File 08 admin primary action styling uses a non-canonical green instead of the governed Sabri Green fallback

`assets/css/admin.css` uses `#166534` / `#14532d` for `.button-primary`, while the active File 08 addendum and `WCA_Central_Governance::SABRI_GREEN` define `#087A4E` as the current primary fallback and File 25 as visual-token owner. Public clinic CSS already uses the governed value. The admin surface therefore has an avoidable local brand-token divergence.

## Reviewed non-defects

- The current governing addendum explicitly supersedes the older orange-primary baseline: Sabri Green `#087A4E` is current primary; orange is secondary/contextual only.
- Public clinic CSS correctly uses `#087A4E` as its primary fallback and preserves responsive, RTL, reduced-motion and forced-colors handling.
- `tests/check-contrast.php` checking orange against dark ink is not itself proof of primary-token ownership; orange remains an allowed contextual/secondary token.
- Central-governance manifest correctly declares File 25 as visual-token owner and File 20 as navigation/shell owner.

## Correction gate

Only after this ledger freeze:
1. Update packaged `readme.txt` so T25 is the active review sequence and T21–T24 are historical/current provenance as applicable, without fabricating a future CI result.
2. Align admin primary/focus styling to the exact Sabri Green fallback while preserving accessible darker hover/focus treatment.
3. Add a permanent T25 R1 regression that binds packaged readme currentness and exact admin primary fallback to the governing contract.
4. Bind that regression into the aggregate suite.
5. Run canonical PHP 7.4/PHP 8.3 source/JS/hygiene and reproducible exact-head candidate verification before T25 R2 begins.

## Evidence boundary

Repository/source/package evidence only. Staging/live plugin files, DB/schema/migration state and visual behavior are not established by this review.
