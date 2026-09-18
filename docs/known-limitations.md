# Known limitations

This repository started empty, and version 1.0.0 is an audited platform foundation—not
the full 119-requirement product release. The following must be completed before the
specification can pass final acceptance:

- Entry edit/revision UI, drafts/save-resume, partial entries, frontend CRUD/views.
- Repeater runtime, conversational/multi-step runtime, signatures, upload finalization,
  private storage, document/PDF generation, booking UI, analytics reports.
- First-party ACF, WooCommerce, gateway, CRM, cloud storage, geolocation, CAPTCHA, AI,
  and competitor migration adapters and credential screens.
- Payment persistence/webhook controllers beyond contracts/state/idempotency schema.
- Survey/quiz persistence and reports beyond field types and pure scoring/statistics.
- Builder panels for rules, calculations, workflows, mappings, payments, views, and
  advanced styling; template browser; export/import; translated catalogs.
- Broader WordPress integration tests, Playwright E2E, axe and manual accessibility
  passes, million-row/load benchmarks, and multisite execution. Clean ZIP install and
  the implemented vertical-slice smoke test pass.

These are explicitly marked incomplete; activation or passing unit tests is not treated
as product completion.
