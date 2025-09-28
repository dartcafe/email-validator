# Email Validator for PHP

Robust email validation for modern PHP.

The **Email Validator** separates **format validity** from **deliverability**, supports **domain typo suggestions**, and lets you plug in custom **providers** for lists and DNS. File/INI list handling is offered via a small adapter.

---

# Features

- **Format-only validity** (`valid`) — syntax & RFC length checks plus basic domain shape.
- **Deliverability prediction** (`sendable`) — DNS: domain existence + MX records (via pluggable resolver).
- **Domain typo suggestions** — configurable provider with a sensible default list.
- **Pluggable list checks** — allow/deny by domain or full address via a `ListProvider`.
- **Clear result model** — typed getters for PHP and compact JSON for APIs.
- **OpenAPI 3** — attribute-based spec; optional Swagger UI in the demo.
- **PHP 8.1+**, Psalm-typed, PHPUnit-tested, PSR-12 styled.

---

## Installation

```bash
composer require dartcafe/email-validator
```

> Requires PHP **8.1+**. For Internationalized Domain Names (IDN) support, enable `ext-intl` (recommended).

---

## Quick start (with INI + text files)

The core is file-system agnostic. If you want INI + text files, use the included adapter.

```php
<?php

use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Adapter\IniListProvider;

// Optional: lists via INI + text files (one value per line)
$lists = is_file(__DIR__ . '/config/lists.ini')
    ? IniListProvider::fromFile(__DIR__ . '/config/lists.ini')
    : null;

// Create validator (default typo suggestions included)
$validator = new EmailValidator(lists: $lists);

// Validate
$result = $validator->validate('User+tag@Straße.de');

// Work with the result
if ($result->isValid()) {
    $sendable   = $result->isSendable();   // DNS + MX OK?
    $normalized = $result->getNormalized(); // local@ascii-domain (domain lowercased)
    $suggestion = $result->getSuggestion(); // correction (if any)
    $reasons    = $result->getReasons();    // format/DNS reasons
    $warnings   = $result->getWarnings();   // e.g. deny-list hits
}
```

### JSON shape (example)
```json
{
  "query": "User+tag@Straße.de",
  "corrections": {
    "normalized": "User+tag@strasse.de",
    "suggestion": null
  },
  "simpleResults": {
    "formatValid": true,
    "isSendable": true,
    "hasWarnings": false
  },
  "reasons": [],
  "warnings": [],
  "dns": {
    "domainExists": true,
    "hasMx": true
  },
  "lists": [
    {
      "name": "deny_disposable",
      "humanName": "Disposable domains",
      "type": "deny",
      "checkType": "domain",
      "matched": false,
      "matchedValue": null
    }
  ]
}
```

> **Terminology**
> - `valid` — only the **syntax/format** aspect.
> - `sendable` — format OK **and** DNS suggests deliverability (domain exists & MX present).
> - `warnings` — informative (e.g. deny-list hit); they do **not** flip `sendable`.

---

## Lists via INI + text files

**`config/lists.ini`**
```ini
[deny_disposable]
type = "deny"
listFileName = "lists/disposable_domains.txt"
checkType = "domain"
listName = "deny_disposable"
humanName = "Disposable domains"

[allow_vip_customers]
type = "allow"
listFileName = "lists/vip_addresses.txt"
checkType = "address"
listName = "allow_vip"
humanName = "VIP customers"
```

**`config/lists/disposable_domains.txt`**
```
# one domain per line (case-insensitive)
mailinator.com
tempmail.org
```

**`config/lists/vip_addresses.txt`**
```
# one address per line (case-insensitive)
vip.customer@example.com
ceo@example.com
```

Load with:

```php
use Dartcafe\EmailValidator\Adapter\IniListProvider;

$lists = IniListProvider::fromFile(__DIR__ . '/config/lists.ini');
$validator = new EmailValidator(lists: $lists);
```

Each configured section becomes a `ListOutcome` in the result. If a **deny** list matches, a warning like `deny_list:<name>` is added (informational by default).

---

## Pluggable providers (advanced)

The validator depends on minimal contracts so you can swap implementations easily.

### ListProvider
```php
namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\ListOutcome;

interface ListProvider {
    /** @return list<ListOutcome> */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): array;
}
```

### DnsResolver
```php
namespace Dartcafe\EmailValidator\Contracts;

/** @return array{0:?bool, 1:?bool} [domainExists, hasMx] */
interface DnsResolver {
    public function check(string $asciiLowerDomain): array;
}
```

### DomainSuggestionProvider
```php
namespace Dartcafe\EmailValidator\Contracts;

interface DomainSuggestionProvider {
    public function suggestDomain(string $asciiLowerDomain): ?string;
}
```

Provide your own implementations and pass them into the constructor:

```php
$validator = new EmailValidator(
    suggestions: $mySuggestions,      // implements DomainSuggestionProvider
    lists:       $myListProvider,     // implements ListProvider
    dns:         $myDnsResolver       // implements DnsResolver
);
```

A default DNS resolver and a default suggestion provider ship with the library.

---

## REST & OpenAPI

The repo includes OpenAPI attribute definitions (`docs/`) to generate `public/openapi.json`. Example:

```bash
vendor/bin/openapi --bootstrap vendor/autoload.php --format json --output public/openapi.json docs
```

Endpoints in the spec:
- `POST /validate` — `{ "email": "user@example.com" }`
- `GET  /validate?email=user@example.com`

A separate demo app (UI + lists editor + Swagger UI) is available in a companion repository.

---

## Development

```bash
# coding standards
composer cs
composer cs:fix

# static analysis
vendor/bin/psalm --no-cache

# tests
composer test
```

---

## License

See [LICENSE](./LICENSE).