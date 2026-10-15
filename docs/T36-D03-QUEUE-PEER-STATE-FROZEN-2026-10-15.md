# File08 T36-D03 — Frozen pre-patch queue peer-state defect

Repository branch baseline: PR26 head `4a0e5cb0b0fcbd6f038946207ee94ab83eb1bdb1`; reviewed after the 40 source screens, before this specific correction. **This is repository analysis, NOT a live incident root-cause proof.**

`includes/class-wca-future24.php` `queue_position()` checks current subject appointment status (`confirmed` or `reschedule_pending`) but the **second aggregate COUNT query** counts prior `F08-FUT-15` `arrived` records without examining the _other_ appointments' current `_swc_status`. A previously arrived appointment cancelled/completed/no_show may still remain as an unexpired operational arrival record and be counted ahead. PR26 itself expressly left this as unresolved staging acceptance.

Expected correction: enforce current valid appointment status for **each counted peer** using only File08's canonical WordPress appointment post and current `_swc_status`, including accepted/reschedule legacy aliases. Retain DISTINCT, short-lived arrival expiry, same-clinic matching, purpose-limited private response, SQL failure 503, and no disclosure of peer refs. Add regression to execute actual method and inspect query construction. Verify with real MySQL cancelled-peer fixtures on staging before release.
