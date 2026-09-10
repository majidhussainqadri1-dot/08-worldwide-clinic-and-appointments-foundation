# T22 R9 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `8409a646cec0ac0de89af7c426d911e81bda888b` (T22 R8 corrected exact HEAD; canonical File 08 Complete Master Plan Quality run `34441290317` completed successfully before R9 opened).

## Mandatory discipline

R9 was completed read-only before this ledger was created. No R9 source/test/package correction was applied while the review was open. This ledger is the post-review freeze required before correction.

## Review scope

Fresh frontend/internationalization/accessibility/timezone review of canonical clinic, booking, patient appointments, appointment detail, dashboard, client-side booking/transition/calendar controls, continuity UI, RTL/reflow/focus behavior, protected-route headers, and compatibility-facing presentation. Governing release gates include keyboard/focus/labels/contrast/zoom/reduced-motion/RTL acceptance plus American-English base with Urdu/Arabic/RTL and timezone/currency/number/date correctness.

## Frozen defect ledger

### R9-D1 — Canonical appointment/service lifecycle labels bypass translation APIs

`WCA_Frontend` renders appointment status, transition-action text, clinic status, and consultation type by transforming internal machine keys with `ucfirst()` / `str_replace()` rather than mapping them through translatable human labels. `assets/js/clinic.js` also builds transition confirmation/success text from raw machine-state keys. These strings therefore remain English/internal-key shaped on Urdu/Arabic interfaces and can expose forms such as `in_person` rather than a localized presentation label.

Required correction after freeze: use explicit translatable presentation maps for appointment statuses, clinic statuses and consultation types; client confirmation/success messaging must use already-localized human text rather than raw state keys.

### R9-D2 — Canonical appointment date/time presentation is timezone-correct but not locale-aware

`WCA_Frontend::appointment_time_label()` correctly converts canonical UTC to the stored patient timezone, but then formats it with raw `DateTimeImmutable::format( 'F j, Y g:i a' )`. That bypasses WordPress locale-aware date formatting, so month/day-period presentation remains English even when the UI locale is Urdu/Arabic.

Required correction after freeze: retain the exact UTC → stored-IANA-timezone conversion, but render through locale-aware WordPress date formatting without changing the canonical timestamp or timezone.

### R9-D3 — Booking form can pair a browser timezone with the wrong initial calendar date

The server initializes `date_from` with `wp_date( 'Y-m-d' )`, which uses the site timezone. On page load `clinic.js` replaces the timezone field with the browser IANA timezone but leaves `date_from` unchanged. Around timezone/date boundaries, a patient in another region can therefore search the site's calendar day while the request says it is the patient's browser timezone.

Required correction after freeze: when a trustworthy browser timezone is adopted, initialize the date input from the browser-local calendar date as the matching local date and set a matching minimum; preserve user changes and continue server-side validation as authoritative.

### R9-D4 — Auto-generated continuity field labels and read-only field names contain untranslated raw strings

`assets/js/continuity.js` creates the automatic pre-visit form from a specification array but appends `spec[1]` directly rather than `tr(spec[1])`. Its read-only intake renderer also presents raw payload keys by replacing underscores. These client-generated labels bypass the plugin text domain even though the surrounding UI uses `wp.i18n`.

Required correction after freeze: pass generated human labels through `tr()` and use an explicit translation-safe field-label map for read-only payload keys, with a safe translated fallback.

## Evidence-domain boundary

Repository/source review only. Browser/device rendering, installed Urdu/Arabic catalog completeness, real theme interactions, Hostinger cache behavior, real mobile keyboard behavior, actual browser timezone availability, staging screenshots, and live production behavior are not established here.

## Closure gate

R9 is not closed by this ledger. All four defects must be corrected as one post-freeze batch, permanent regression coverage must be added, and the resulting exact HEAD must pass canonical PHP 7.4/PHP 8.3 quality gates plus reproducible independently verified candidate packaging before R10 may begin.
