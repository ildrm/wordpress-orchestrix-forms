# Release checklist

- [x] Metadata identity and GPL license
- [x] PHP syntax, PSR-12, PHPUnit/domain tests, PHPStan level 6
- [x] TypeScript strict build, ESLint, JavaScript unit tests
- [ ] Fresh repeatable PHP/JS dependency advisory audit (offline npm pass; online registries timed out/reset)
- [x] Production ZIP excludes development files
- [x] Clean WordPress create/publish/draft/render/submit/encrypt/persist/queue/authorization smoke
- [ ] Browser E2E and accessibility suite
- [x] Clean WordPress ZIP install, activation, and schema migration
- [ ] All feature-matrix rows implemented
- [ ] Live optional integrations tested with sandbox credentials
- [ ] High-volume and concurrency benchmark report
- [ ] Manual WCAG 2.2 AA review

The unchecked items block a production-complete PASS.
