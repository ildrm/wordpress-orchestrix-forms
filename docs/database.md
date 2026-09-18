# Database schema

All tables use the current site's `$wpdb->prefix`, preserving multisite isolation.

| Table | Purpose | Important indexes |
|---|---|---|
| `orchestrix_forms` | Stable form identity/settings | UUID, slug, status/update |
| `orchestrix_form_versions` | Immutable published schema history | form/version, form/state |
| `orchestrix_submissions` | Entry envelope | UUID, form/date, status/date, user/form |
| `orchestrix_submission_values` | Typed and privacy-classified values | entry/field, strict uniqueness, numeric/date |
| `orchestrix_submission_revisions` | Entry before/after history | entry/revision |
| `orchestrix_drafts` | Expiring save/resume payloads | token hash, expiry, user/form |
| `orchestrix_jobs` | Retryable background work | ready queue, idempotency |
| `orchestrix_payments` | Payment aggregate | UUID, gateway reference, idempotency |
| `orchestrix_workflow_runs` | Workflow execution state | workflow/status, entry/status |
| `orchestrix_audit_log` | Security/product audit events | object/date, actor/date |
| `orchestrix_idempotency` | Replay/result records | scope/key, expiry |
| `orchestrix_capacity` | Atomically reserved slots | resource/slot |
| `orchestrix_analytics_daily` | Bounded aggregate reporting | form/date/event/dimension |

Migrations use `dbDelta`, are idempotent, and record `orchestrix_forms_db_version`.
Operational tables intentionally avoid `wp_posts`/`postmeta`. Application-level
integrity is used because WordPress table prefixes and multisite make foreign keys
fragile. Strict unique fields are enforced by a database unique index, not a preflight
query. Capacity increments use a single conditional `UPDATE`.
