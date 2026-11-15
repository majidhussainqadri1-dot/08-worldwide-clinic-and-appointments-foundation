# File08 V12.3 — Future24 waitlist and per-clinic policy collections fail-open SQL freeze

**2026-11-15. Exact source base:** Draft PR62 HEAD `436818ac7349340890c1fe4f91c260e377dae091`, descended from draft PR61 and PR60. GitHub `main=d086840f7e2ac0ed328006385f064e0e2fb58cb1` remains separate. None is proved Live. **V12.3 F08-FUT-01 P0 waitlist; F08-FUT-11 P0 restricted questionnaires; F08-FUT-13 P1 prerequisite policy; F08-NFR-003/004 reliability/performance.**

## Exact pre-patch source root cause
Two protected Future24 reads in `includes/class-wca-future24.php`:
- `waitlist_candidates( clinic_id )` iterates paged active F08-FUT-01 candidates through a Generator. It only throws if `null === rows && last_error`. When `get_results` returns `[]` despite a fresh SQL error, the generator silently ends as if there are no eligible waiting patients. When returned `false` with no error, casting `(array)false` yields a bogus candidate.
- `feature_rows_for_clinic( feature, clinic, status, shape )` lists policy and questionnaire rows. A first or later SQL failure producing `[]` + error silently returns zero or partial policy results; non-array `false` produces a fake row. Callers already handle WP_Error when returned, so a typed error is expected.

**Direct executable local PHP 8.4 reproduction using actual PR60-equivalent method code:** valid first-page empty => 0; SQL first-page []+error => 0 **wrong**; 100 good rows followed by second-page []+error => 100 partial **wrong**; malformed false => 1 bogus row **wrong**. This happened in both methods. These are source-only findings; no proof of a Live failure.

## Bounded correction, no patch stacking
- Only two methods: clear `$wpdb->last_error` just before each `get_results`; treat `!is_array(rows) || last_error !== ''` as failure; throw `RuntimeException('wca_waitlist_candidate_read_failed')` in generator; return preexisting `WP_Error('wca_future24_policy_read_failed',503)` in policy reader.
- Iterate only verified row arrays; reject invalid row shape/missing positive ID before cursor progression so a false entry or a stalled 100-row keyset cannot be silently accepted.
- Preserve waitlist no-auto-book policy, File19 notification ownership, existing idempotency, F08-FUT-11 encrypted-answer owner and File08 role checks. No plugin version/schema/migration or patient data writes.
- Native source regression tests: first/second page fresh SQL error, malformed non-array false, valid zero, stale previous SQL error, malformed row entries (expect fail-closed), with actual private methods invoked. Source CI PHP7.4/8.3 + 40 whole-suite cycles + 3 disposable WP/MariaDB and reproducible ZIP; this does not replace actual owner staging/privacy testing.

**Accounting:** previous PR60 = 20, PR61 = 1, PR62 = 1, this = 1 new grouped list-read integrity risk affecting two methods → **23 scoped source groups**, subject to final successful CI. `main` merged 0, staging verified 0, Live-resolved 0. Not an exhaustive count of File08 defects.

**Live access:** Jetpack `site_disconnected`; WPVibe no linked site/earlier HTTP530; exact installed package/DB/migration/changing dependencies unknown. No Live writes, no claim of resolution. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**