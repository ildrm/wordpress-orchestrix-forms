# Integrations and migrations

Core defines field, payment gateway, workflow action, HTTP, storage, and hook contracts.
Generic webhook delivery is functional and uses HTTPS, SSRF checks, HMAC, retries, and
idempotency headers. ACF, WooCommerce, CRM, marketing, storage, calendar, payment, PDF,
AI, and competitor-migration adapters are not bundled in 1.0.0. They must be delivered
as independently testable adapters and must fail closed when their host plugin or live
credentials are absent.

No third-party service was claimed as live-verified. Form export/import and competitor
migration user interfaces are not present in this build.
