# Requirements audit (1–119)

Status keys: **I** implemented, **P** partial, **N** not implemented, **V** verification/release requirement.

| # | Status | Evidence / note |
|---:|:---:|---|
| 1 | I | `orchestrix-forms.php`; metadata fixed to author/version/slug |
| 2 | I | Architecture/security/UX/test decisions represented in source and reports |
| 3 | P | No prohibited placeholders; full feature set remains incomplete |
| 4 | I | Empty repository and toolchain inspected before implementation |
| 5 | I | Pipeline and architecture documentation |
| 6 | P | Modular services/contracts/DTO contexts; some WordPress boundary coupling remains |
| 7 | P | Bounded source directories exist for implemented modules |
| 8 | P | First-class persistence for core aggregates; many advanced aggregates absent |
| 9 | P | Immutable publish/version reference and draft/public isolation verified; compare/restore/duplicate UI absent |
| 10 | I | `FieldTypeInterface`, registry, extension hook |
| 11 | P | Full type catalog; specialized rendering/behavior incomplete |
| 12 | P | Schema/aggregate support; repeater UI/runtime incomplete |
| 13 | P | Immediate three-pane builder with drag, buttons, undo/redo; advanced panels absent |
| 14 | P | Simple/advanced control shown; advanced panels absent |
| 15 | P | CSS tokens, responsive/RTL/reduced motion; dark theme not finished |
| 16 | P | Semantic controls/error UX; automated/manual WCAG run pending |
| 17 | I | Server render, minimal JS, AJAX/non-AJAX, error focus/status |
| 18 | P | Nested AST and operators server-side; all actions/client runtime absent |
| 19 | I | Parser, functions, arrays, dependencies, cycle rejection, authoritative server run |
| 20 | P | Core/conditional/extensible validation; async/remote/availability absent |
| 21 | P | Draft schema/token column/cleanup; endpoints and resume UX absent |
| 22 | P | States/schema concepts; capture/conversion absent |
| 23 | P | Ordered secure pipeline; upload finalization/authorization variants incomplete |
| 24 | P | Paginated REST/list UI; advanced entry management absent |
| 25 | P | Revision table; editing/restore UI absent |
| 26 | P | Owner read policy; full CRUD absent |
| 27 | N | View/application subsystem absent |
| 28 | N | WordPress mapping engine absent |
| 29 | N | ACF adapter absent; unrelated features do not require ACF |
| 30 | N | User actions absent |
| 31 | P | Step field/schema; navigation runtime absent |
| 32 | N | Conversational presentation absent |
| 33 | P | Survey fields and NPS statistics; reports/storage incomplete |
| 34 | P | Quiz fields and weighted scorer; attempts/outcomes incomplete |
| 35 | P | Poll field only |
| 36 | P | Atomic capacity primitive; scheduling absent |
| 37 | P | Atomic resource/slot limit primitive; UI absent |
| 38 | P | Security validator; upload lifecycle absent |
| 39 | N | Cloud storage adapters absent |
| 40 | N | Document/PDF system absent |
| 41 | P | Field type only |
| 42 | P | Queued conditional email; templates/logs/providers incomplete |
| 43 | P | Context-safe merge tags; full tag catalog absent |
| 44 | P | State machine, conditional actions, extension hook; action catalog incomplete |
| 45 | I | Abstracted DB queue, retries, backoff, stale-lease recovery, inspectable states |
| 46 | P | Outgoing HTTPS/HMAC/idempotency/retry; inbound endpoint absent |
| 47 | P | SSRF guard and safe WP request path; general action UI absent |
| 48 | P | Contract/hook architecture; named first-party adapters absent |
| 49 | P | Payment state model/table/contract; child aggregates incomplete |
| 50 | P | State/refund/currency schema; payment flows absent |
| 51 | P | Gateway contract; official adapters absent |
| 52 | N | WooCommerce integration absent and gracefully uncoupled |
| 53 | P | Aggregate table; event/report jobs absent |
| 54 | P | Field types only; provider abstraction absent |
| 55 | P | Rate, honeypot, timing, link score; external providers absent |
| 56 | P | Threat model and controls documented; full adversarial suite pending |
| 57 | P | Secret fields encrypted at rest and capability-gated on read; credentials UI/rotation absent |
| 58 | P | Dedicated operational tables for implemented aggregates |
| 59 | P | Version/index/transactions/pagination; rollback tooling/profiling incomplete |
| 60 | P | Indexed paginated design; million-row benchmark pending |
| 61 | P | CSV injection primitive; export jobs/UI absent |
| 62 | N | Safe import UI absent |
| 63 | N | Competitor migrations absent |
| 64 | P | Versioned forms/submissions API with permissions; resources incomplete |
| 65 | P | Stable field/payment/workflow extension points; full registry catalog absent |
| 66 | P | Forms/queue/diagnostics commands; export/migration commands absent |
| 67 | I | Native dynamic block with inspector and server preview |
| 68 | I | Shortcode and shared PHP renderer |
| 69 | P | Dependency-free embed design; builder-specific tests pending |
| 70 | P | Inherited clean token theme; form style editor absent |
| 71 | P | i18n calls/RTL logical CSS; locale format/catalog testing pending |
| 72 | P | Field classifications/storage; WP exporter/eraser/retention absent |
| 73 | P | Audit schema; event writer/instrumentation absent |
| 74 | P | Minimal core/dynamic features; gzip budget measured later |
| 75 | I | Schema inspector compiles feature dependency list |
| 76 | P | Immediate normalized local state/debounced intent; virtualization absent |
| 77 | N | Cache service absent |
| 78 | N | AI provider/UI absent |
| 79 | N | Template catalog absent |
| 80 | P | Dates/auth/capability restrictions; totals/IP/user/token windows incomplete |
| 81 | I | Race-safe DB unique scope for global/user |
| 82 | P | Inline message only; redirect/page conditional flows absent |
| 83 | P | Defaults supported in schema; dynamic providers absent |
| 84 | P | Static choices; dynamic sources absent |
| 85 | P | Diagnostics page; full test console absent |
| 86 | P | Test flag/storage; sandbox simulations absent |
| 87 | I | Domain errors, safe public API errors, inspectable job failures |
| 88 | P | DB/REST/queue diagnostics; downloadable redacted report absent |
| 89 | N | Portable form import/export absent |
| 90 | P | Network activation/per-site tables; runtime verification pending |
| 91 | I | Preserve default, explicit `remove_all` cleanup setting |
| 92 | I | Dedicated capabilities installed for administrators |
| 93 | P | Strict PHP/TS/PSR-4, PSR-12 gate, and static analysis; full WordPress Coding Standards ruleset not installed |
| 94 | I | Zero runtime third-party dependencies |
| 95 | P | PHPUnit, domain/JS tests, and WordPress Docker smoke; broad integration/browser E2E absent |
| 96 | N | axe/manual accessibility suite absent |
| 97 | P | Selected adversarial unit cases; complete attack suite absent |
| 98 | N | Dataset/load benchmarks absent |
| 99 | N | Compatibility matrix execution absent |
| 100 | P | Fail-fast local verification and CI PHP/JS/audit/package gates; integration/E2E CI jobs absent |
| 101 | I | Production ZIP installed/activated on clean Docker WordPress; migration passed |
| 102 | P | Core architecture/API/security/performance/testing docs; feature guides incomplete |
| 103 | N | Demo fixtures absent |
| 104 | N | First-time/advanced admin manual acceptance pending |
| 105 | P | Publish confirmation/immutable history; all destructive actions absent |
| 106 | P | Conditional asset loader; network verification pending |
| 107 | V | No known dependency advisory; full security release gate not satisfied |
| 108 | V | Independent review reports partial; corrections ongoing |
| 109 | V | Contact/calculation/queue/authorization smoke passed; remaining scenarios not executed |
| 110 | V | Actual command evidence recorded in final report |
| 111 | I | `docs/feature-matrix.md` |
| 112 | I | Project explicitly marked incomplete |
| 113 | P | Automated scans run; full translation/install checks pending |
| 114 | P | Source/docs/package delivered; several required reports are limitation reports |
| 115 | I | Final response follows required format |
| 116 | I | Security/correctness-first choices evident in pipeline/schema |
| 117 | P | Concepts used without copying UI/assets; ecosystem breadth incomplete |
| 118 | P | Core platform foundation only |
| 119 | P | Inspection through packaging performed; full acceptance cannot pass |
