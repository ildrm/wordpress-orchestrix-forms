# Security policy

Report vulnerabilities privately to `security@ildrm.com`. Do not open a public issue
before a fix is available. Include affected version, reproduction steps, impact, and a
minimal proof of concept. We aim to acknowledge reports within five business days.

Orchestrix Forms treats nonces as CSRF signals, not authorization. Administrative
routes require dedicated capabilities; public submissions require short-lived signed
form/version tokens and rate limiting. Secrets use libsodium authenticated encryption.
Outbound requests require HTTPS and reject private, reserved, loopback, and link-local
addresses. The plugin never stores raw card data.

See [docs/security.md](docs/security.md) for the threat model and verification record.
