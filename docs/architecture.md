# Architecture

Orchestrix Forms is a modular monolith. The request path is:

```text
schema → presentation → rules → normalization → validation → anti-spam
       → transaction → event/job → workflow/integration → output
```

Published rows in `orchestrix_form_versions` are immutable. Every submission stores
both `form_id` and the exact `form_version_id`. Field behavior is registered through
`FieldTypeInterface`; rules and calculations are pure deterministic services. WordPress
is isolated at repositories, HTTP, capability, renderer, and hook boundaries.

Public forms are server-rendered. The core frontend asset is loaded only when a form is
rendered; feature assets are selected from the compiled schema feature graph. Jobs are
claimed transactionally and use exponential backoff. Integration actions receive an
idempotency key.

Extension points include `orchestrix_forms_register_fields`,
`orchestrix_forms_validation_errors`, `orchestrix_forms_spam_score`,
`orchestrix_forms_submission_created`, and
`orchestrix_forms_workflow_action_{type}`.
