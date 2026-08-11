from pathlib import Path

# Runtime identity.
p = Path('worldwide-clinic.php')
s = p.read_text()
s = s.replace(' * Version: 1.2.2\n', ' * Version: 1.2.3\n', 1)
s = s.replace("define( 'WCA_VERSION', '1.2.2' );", "define( 'WCA_VERSION', '1.2.3' );", 1)
p.write_text(s)

p = Path('includes/class-wca-contracts.php')
s = p.read_text().replace("const RUNTIME_VERSION                 = '1.2.2';", "const RUNTIME_VERSION                 = '1.2.3';", 1)
p.write_text(s)

# Permanent source-level gates should always assert the current runtime identity.
for p in Path('tests').glob('*.php'):
    s = p.read_text()
    if '1.2.2' in s:
        p.write_text(s.replace('1.2.2', '1.2.3'))

# Correct the third-cycle schema source assertions: continuity/Future24 own their additive schemas.
p = Path('tests/third-ten-review-regressions.php')
s = p.read_text()
s = s.replace("$contracts=t310src('includes/class-wca-contracts.php');\n", "$contracts=t310src('includes/class-wca-contracts.php');\n$continuity=t310src('includes/class-wca-continuity-secure.php');\n$future=t310src('includes/class-wca-future24.php');\n", 1)
s = s.replace("t310has('continuity schema unchanged',$contracts,\"CONTINUITY_SCHEMA_VERSION       = '1.1.0'\");\nt310has('future schema unchanged',$contracts,\"FUTURE24_SCHEMA_VERSION          = '1.0.0'\");", "t310has('continuity schema unchanged',$continuity,\"const SCHEMA_VERSION = '1.1.0'\");\nt310has('future schema unchanged',$future,\"const SCHEMA_VERSION   = '1.0.0'\");", 1)
p.write_text(s)

# Repository README current identity + third-cycle summary.
p = Path('README.md')
s = p.read_text()
s = s.replace('- Runtime candidate: **1.2.2**', '- Runtime candidate: **1.2.3**', 1)
third = "\nThe **third fresh 10-round corrective audit** moves the same invariants to canonical roots where cross-cutting guards were insufficient: stale idempotency is fail-closed in the repository itself, payment and transition preconditions are enforced in the service root, protected mutations are no-store/noindex, ICS output strictly validates persisted UTC timestamps, outbox claims atomically re-check eligibility, and service/availability doctor assignment requires current clinic-serving authority rather than eligibility alone.\n"
anchor = "The **second fresh 10-round corrective audit** further hardens administrator transition purpose/step-up checks, requires explicit slot-hold replay keys, namespaces hold replay identity by patient, fails closed on ambiguous stale mutation reservations, strictly validates Future24 date/time inputs, removes native numeric identifiers from Future24 REST DTOs, and serializes outbox dispatch so cron/shutdown workers cannot overlap. Every supported mutation entry point remains guarded by authorization, rate/replay controls, and state/object constraints.\n"
if third.strip() not in s:
    s = s.replace(anchor, anchor + third, 1)
p.write_text(s)

# Candidate status current identity and audit description.
p = Path('STATUS.md')
s = p.read_text()
s = s.replace('- Runtime candidate: **1.2.2**', '- Runtime candidate: **1.2.3**', 1)
s = s.replace('## Second fresh 10-round corrective audit', '## Third fresh 10-round corrective audit', 1)
s = s.replace('A second sequential 10-round review-and-correct cycle was opened after the v1.2.1 closure. Findings are corrected before the next review proceeds. This cycle adds or strengthens:', 'A third fresh sequential 10-round review-and-correct cycle was opened after the v1.2.2 candidate. Findings are corrected before the next review proceeds. This cycle adds or strengthens:', 1)
s = s.replace('- version/documentation alignment for runtime candidate 1.2.2 while retaining core schema 3.1.0, continuity schema 1.1.0 and Future24 schema 1.0.0.', '- canonical-root stale replay safety, payment/transition authority, protected-response cache controls, strict persisted calendar validation, atomic outbox row claims, doctor-to-clinic service/availability scope, and release/document alignment for runtime candidate 1.2.3 while retaining core schema 3.1.0, continuity schema 1.1.0 and Future24 schema 1.0.0.', 1)
s = s.replace('second-cycle source corrections are present', 'third-cycle source corrections are present')
s = s.replace('older v1.2.1 artifact', 'older v1.2.1/v1.2.2 artifact')
p.write_text(s)

# WordPress readme current candidate while preserving historical changelog entries.
p = Path('readme.txt')
s = p.read_text()
s = s.replace('Stable tag: 1.2.2', 'Stable tag: 1.2.3', 1)
s = s.replace('Version 1.2.2 implements', 'Version 1.2.3 implements', 1)
s = s.replace('and a second fresh 10-round corrective audit.', 'a second fresh 10-round corrective audit, and a third fresh sequential 10-round corrective audit.', 1)
s = s.replace('Download the exact CI-generated File 08 v1.2.2 candidate', 'Download the exact CI-generated File 08 v1.2.3 candidate', 1)
entry = """= 1.2.3 =
* Completed a third fresh sequential 10-round corrective audit on the corrected v1.2.2 source state.
* Moved fail-closed stale idempotency, payment-payer authority and transition preconditions into canonical repository/service roots rather than relying only on cross-cutting REST guards.
* Added no-store/noindex protection for every protected mutation response, strict persisted UTC validation for ICS output, atomic outbox row claims, and current doctor-to-clinic authority checks for service and availability assignment.
* Added a permanent third-ten-review regression gate; core DB schema remains 3.1.0, restricted continuity schema remains 1.1.0, and Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

"""
if '= 1.2.3 =' not in s:
    s = s.replace('== Changelog ==\n\n', '== Changelog ==\n\n' + entry, 1)
p.write_text(s)

# Markdown changelog current release, preserving prior release history.
p = Path('CHANGELOG.md')
s = p.read_text()
entry = """## 1.2.3 — 2026-08-11

- Completed a third fresh sequential 10-round review-and-correct cycle against the corrected v1.2.2 source state.
- Removed canonical repository stale-idempotency auto-takeover and aligned the HTTP stale guard with the actual `http_` reservation scope.
- Enforced patient/current-guardian payment authority and explicit expected-status/version transition preconditions at canonical service roots.
- Applied private no-store/noindex headers to all protected core mutations and strictly validated persisted UTC appointment timestamps before ICS generation.
- Made outbox row claiming atomic by rechecking pending/retry, schedule and lock eligibility in the claim UPDATE itself.
- Required current doctor-to-clinic serving authority for service and availability assignment; global verification/eligibility alone is insufficient.
- Advanced runtime/document identity to `1.2.3`; core schema remains `3.1.0`, restricted continuity schema `1.1.0`, Future24 schema `1.0.0`.
- Added a permanent third-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

"""
if '## 1.2.3 — 2026-08-11' not in s:
    s = s.replace('# Changelog\n\n', '# Changelog\n\n' + entry, 1)
p.write_text(s)
