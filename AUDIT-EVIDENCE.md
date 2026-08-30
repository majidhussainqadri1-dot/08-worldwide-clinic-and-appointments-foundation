# File 08 Audit Evidence

## Source identity

- Archive: `08-worldwide-clinic-and-appointments-foundation-0.1.0.zip`
- SHA-256: `3c891d33b809a87edf3df70945d970a0d62d6cdb96cd34e1c2695751c04bd057`
- Extracted source files: 13
- PHP files: 9
- JavaScript files: 1
- CSS files: 2
- Extracted bytes: 56,444

## Syntax results

- PHP 8.4 lint: 9/9 passed.
- Node.js syntax check: 1/1 passed.
- High-confidence secret-indicator scan: no result.

## Reproducible date-validation observations

The current helper uses `DateTime::createFromFormat('!Y-m-d H:i', ...)` without checking parser warnings or round-tripping the local value.

Observed behavior:

```text
invalid-date-normalizes-to=2026-03-02 10:00:00 -05:00
dst-gap-normalizes-to=2026-03-08 03:30:00 -04:00
```

Inputs were:

```text
2026-02-30 10:00 America/New_York
2026-03-08 02:30 America/New_York
```

## Reproducible contrast observation

The primary button combination is white text on `#FF8A1F`.

```text
contrast(#FF8A1F,#FFFFFF)=2.358:1
```

This is below WCAG AA thresholds.

## Review limitation

This review is an independent source and contract audit. It does not claim that Hostinger staging, WordPress database upgrades, real SMTP delivery, LiteSpeed cache behavior, browser rendering, or multi-account end-to-end workflows were executed. Those remain mandatory after correction.
