# File 08 — Four-Round Review and Correction Record 1.0.1

## Governing basis

This review binds File 08 to the Definitive Master Plan 2026 v3.0, the Comprehensive Master Plan 2026 v2.0, the File 08 Complete Master Plan 2026 v1.0, and the later Founder-approved directives that narrow or supersede earlier decisions.

## Round 1 — Architecture, ownership and lifecycle

Found and corrected: Doctor self-activation bypass; missing draft-to-review institutional gate; cross-clinic service and availability reassignment; insufficient branch/service ownership validation; inconsistent practitioner authority consumption.

## Round 2 — Security, privacy and authorization

Found and corrected: guardian/current-actor IDOR; arbitrary patient selection; blanket administrator appointment visibility; native numeric IDs on public clinic/booking surfaces; unbound public practitioner selection; insufficient purpose and step-up evidence.

## Round 3 — Scheduling integrity and resilience

Found and corrected: arbitrary/off-schedule slot holds; stale slot acceptance; non-atomic hold collision window; weak reschedule-hold binding; release-before-book compensation failure; stale/expired hold booking; review eligibility without expiry.

## Round 4 — UI, accessibility, release and regression governance

Verified and reinforced: green primary identity; 44px touch targets; RTL/reduced-motion/forced-colors support; opaque public references in booking UI; version/schema parity; deterministic packaging; explicit four-round regression suite; source/staging/production classifications remain separate.

## Result

All defects found in the four source-level rounds are corrected in runtime 1.0.1. GitHub CI proves syntax, static contracts, security invariants and reproducible packaging. Hostinger staging, exact dependency runtime, real accounts, restore/rollback, delivery, browser/manual accessibility and Founder acceptance remain external release gates and are not fabricated by this record.
