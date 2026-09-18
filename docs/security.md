# Security review

## Trust boundaries

- Public request data is untrusted through persistence and notification rendering.
- Builder configuration is privileged but never executable code.
- WordPress nonces do not replace capability or ownership checks.
- Remote endpoints, uploaded files, credentials, webhook messages, and payment events
  are separate hostile boundaries.

## Implemented controls

- Dedicated capabilities and per-object owner check for entry REST access.
- Short-lived HMAC form/version submission token, bounded payload count, and keyed
  identity rate limiting.
- Registered-field allowlisting, context-aware sanitization, authoritative server
  validation, restricted regex, and a parser-based calculation language without eval.
- Prepared SQL/WordPress database APIs, transactions, immutable version references,
  atomic unique values and capacity reservation, queue idempotency.
- HTML/attribute/URL/header-specific escaping, merge-tag header newline removal, and
  spreadsheet formula neutralization.
- HTTPS-only outbound URL guard with DNS resolution and private/reserved address
  rejection; redirect following is disabled for webhooks.
- HMAC webhook timestamp signatures and replay-window verification primitive.
- File extension, executable double-extension, byte limit, and server MIME checks.
- Secret-classified submission values are encrypted at rest with libsodium, omitted
  from ordinary reads, and decrypted only for callers with the sensitive-data path;
  secrets are not logged or indexed in plaintext.

## Verification status

PHPUnit/domain adversarial tests cover calculation execution/cycles, unsafe regex,
invalid state transitions, token tampering, replay windows, SSRF targets, malicious
file names/MIME, merge-tag escaping, secretbox tampering, and CSV injection. PHPStan
level 6 and the cached npm advisory audit pass; fresh Packagist/npm advisory requests
were blocked by external timeouts/resets. A broader live WordPress security suite,
real upload endpoint attack tests, gateway webhook tests, and penetration testing
remain required before production release.
