# Development, test, and release

```sh
composer install
npm install
composer verify
composer audit:dependencies
composer package
```

`composer verify` stops at the first failed deterministic check. It includes PHP
syntax, PSR-12, PHPUnit, domain scenarios, PHPStan, TypeScript, the production build,
ESLint, and JavaScript tests. `composer audit:dependencies` is a separate online gate
so an advisory-service outage cannot be mistaken for a code-test failure.

The ZIP generator includes runtime PHP, compiled assets, docs, license, changelog, and
uninstall handler. It excludes source TypeScript, tests, development dependencies,
Node modules, Git metadata, and local files. Install the ZIP on a clean WordPress site,
activate it, create/publish a form, submit it, inspect the entry, run cron, and inspect
the workflow job before promoting a release.
