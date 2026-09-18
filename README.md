# Orchestrix Forms

Orchestrix Forms is a WordPress-native form, data, workflow, survey, quiz, payment,
and low-code application platform. The runtime is dependency-light, server-rendered,
and progressively enhanced. See [docs/](docs/) for architecture, API, security,
operations, and extension documentation.

## Requirements

- WordPress 6.4+
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+

## Development

```sh
composer install
npm install
composer verify
composer audit:dependencies
composer package
```

`composer verify` is the deterministic fail-fast gate. Dependency audits are kept in
`audit:dependencies` because they require live Packagist and npm advisory services.

## Embedding

```text
[orchestrix_form id="123"]
```

Or in PHP:

```php
echo \Orchestrix\Forms\Plugin::instance()->renderer()->render( 123 );
```

Copyright Shahin Ilderemi. Licensed under GPL-2.0-or-later.
