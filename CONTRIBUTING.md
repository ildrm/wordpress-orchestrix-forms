# Contributing

Use PHP 8.1+, Node 20+, and Composer 2. Run `composer test`, `composer analyse`,
`npm run typecheck`, `npm run build`, `npm run lint`, and `npm test` before proposing
changes. New boundary code must validate input, authorize the object operation, escape
for its output context, and include an adversarial test. Keep domain logic independent
of WordPress globals where practical. Public extension contracts follow semantic
versioning; breaking changes require a major release.
