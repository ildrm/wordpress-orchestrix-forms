# Verification report

Executed on 2026-09-18. Counts below are observed command results, not estimates.

| Command | Result | Passed | Failed | Skipped / note |
|---|---|---:|---:|---|
| `composer test` | PASS | 53 PHP syntax files, PSR-12, 10 PHPUnit tests / 35 assertions, 7 domain scenarios | 0 | WordPress integration separate |
| `composer analyse` | PASS | PHPStan level 6 | 0 | WP-CLI command excluded because its external stubs are not installed |
| `composer verify` | PASS | All deterministic PHP and JavaScript gates in one fail-fast command | 0 | Online advisory services intentionally separate |
| `npm run typecheck` | PASS | Strict TypeScript project | 0 | — |
| `npm run build` | PASS | 3 compiled TypeScript modules | 0 | Block script is precompiled runtime JS |
| `npm run lint` | PASS | All production JS bundles | 0 | — |
| `npm test` | PASS | 2 builder-state tests | 0 | Browser component tests absent |
| `npm audit --offline --audit-level=high` | PASS | 0 vulnerabilities | 0 | Cached advisory data; online repeat hit `ECONNRESET` |
| `npm audit --audit-level=high` | BLOCKED | 0 | 0 | Registry advisory endpoint reset the connection, including outside the sandbox |
| `composer audit --locked` | BLOCKED | 0 | 0 | Packagist advisory endpoint timed out, including outside the sandbox; project-local cache permission issue is fixed |
| package extraction syntax loop | PASS | 45 packaged PHP files | 0 | Temporary extraction deleted |
| Docker clean install/activation | PASS | ZIP install, activation, metadata, DB v1.0.0 | 0 | Disposable stack removed afterward |
| Docker functional smoke | PASS | create, publish, draft isolation, render, submit, calculate, encrypted secret storage, queue lease recovery, deny anonymous entry | 0 | Total 13.5; secret encrypted/decrypted; draft isolated; denial HTTP 401 |

## Mandatory E2E scenarios

- A Contact: core create/publish/render/submit/entry path passed; live mail notification was not configured.
- C Calculation: server result `3 × 4.5 = 13.5` passed; browser comparison not run.
- H Workflow failure: queue processing and expired-worker lease recovery passed; forced remote failure/retry exhaustion not run.
- M File attack: executable double-extension and MIME mismatch rejection passed in PHPUnit; live upload endpoint is not implemented.
- O Authorization attack: anonymous access to private entry returned HTTP 401.
- P Performance: core asset sizes passed budget; browser network trace not run.
- B, D-G, I-L, N: not run because their corresponding product modules are incomplete.

Accordingly, the complete mandatory acceptance suite is FAIL even though the clean
installation and implemented vertical slice pass.
