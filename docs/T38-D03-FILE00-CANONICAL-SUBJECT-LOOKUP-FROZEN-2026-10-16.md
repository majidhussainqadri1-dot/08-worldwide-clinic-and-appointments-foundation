# T38-D03 — Frozen File00 subject reverse-lookup contract mismatch

Date: 2026-10-16; this was independently discovered in the post-correction companion review, after the original T38 40-round freeze. This is an additional repository-source integration finding, not a live root-cause claim.

- Exact File08 baseline: b25705866b46188db7a73a83f61ffc0aee402e8f. File00 independent main: 2fa7c022ee9cd1b65432e900579512f304532442 (plugin 1.2.44, DB 1.4.5, CF01 contract 1.1.0).
- File08 WCA_Future24::subject_user_id calls smc_get_user_id_by_subject_uuid() if available. Its fallback reads usermeta '_smc_subject_uuid'.
- Complete read-only scan of all 24 runtime PHP files of File00 found no such resolver function and no use of '_smc_subject_uuid'. File00's SMC_CF01_Contract::ensure_subject_uuid() owns '_smc_platform_uuid_v1'.
- Hence valid Future24 support/interpreter subjects can fail to resolve with these exact companion repository builds. Current installed/production status unverified.

Proposed owner-compliant correction: File00 adds bounded read-only exact-unique resolver using its own canonical usermeta with no unsafe authority promotion. File08 requires that helper and fails closed when unavailable instead of directly reading the wrong or internal owner metadata. Both PRs need independent native regression and exact-head CI, paired staging installed-version/schema/companion proof, and live verification. An unrelated successful File08 CI never proves File00 installed API compatibility.