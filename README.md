# Email Validator for PHP

Robust email validation for modern PHP.
The **Email Validator** separates **format validity** from **deliverability** and lets you plug in **deny/allow lists** and **domain typo suggestions**. A minimal REST endpoint and OpenAPI spec are included for quick integrations.

---

## Features

- **Format-only validity** (`valid`) — strict checks incl. length and basic domain shape.
- **Deliverability prediction** (`sendable`) — DNS lookup: domain existence + MX records.
- **Domain typo suggestions** — configurable provider with a default list.
- **Configurable lists** — allow/deny checks against plain text files (by domain or full address).
- **Clear result model** — machine-friendly JSON with reasons/warnings + getters for PHP.
- **OpenAPI 3** — generated spec; optional Swagger UI.
- **PHP 8.1+**, Psalm-typed, PHPUnit-tested, PSR-12 styled.

---

## Installation

```bash
composer require dartcafe/email-validator
```

> Requires PHP **8.1+**. For Internationalized Domain Names (IDN) support, enable `ext-intl` (recommended).

---

## Quick start

```php
<?php

use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Lists\ListManager;

// Optional: load allow/deny lists from an INI file
$lists = is_file(__DIR__ . '/config/lists.ini')
    ? ListManager::fromIni(__DIR__ . '/config/lists.ini')
    : null;

// Create the validator (default domain suggestions are included)
$validator = new EmailValidator(lists: $lists);

// Validate
$result = $validator->validate('User+tag@Straße.de');

// Work with the result (typed getters)
if ($result->isValid()) {
    $sendable  = $result->isSendable();     // DNS + MX OK?
    $normalized = $result->getNormalized(); // normalized local@ascii-domain (lowercased domain)
    $suggestion = $result->getSuggestion(); // recommended correction (if any)
    $reasons    = $result->getReasons();    // format/DNS reasons (if any)
    $warnings   = $result->getWarnings();   // non-fatal warnings (e.g. deny-list hits)
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
      "typ": "deny",
      "checkType": "domain",
      "matched": false,
      "matchedValue": null
    }
  ]
}
```

> **Terminology**
> - `valid` — only about **syntax/format** (RFC length constraints, basic domain shape).
> - `sendable` — **format OK** *and* **DNS suggests deliverability** (domain exists & MX present).
> - `warnings` — informational (e.g. matched deny-list) and do **not** change `sendable`.

---

## Lists (allow/deny) via INI + text files

You can maintain lists in plain text files (one entry per line; `#` for comments) and wire them up in an INI file.

**`config/lists.ini`**
```ini
[deny_disposable]
typ = "deny"
listFileName = "lists/disposable_domains.txt"
checkType = "domain"
listName = "deny_disposable"
humanName = "Disposable domains"

[allow_vip_customers]
typ = "allow"
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
vip.customer@example.com
ceo@example.com
```

Load them with:

```php
use Dartcafe\EmailValidator\Lists\ListManager;

$lists = ListManager::fromIni(__DIR__ . '/config/lists.ini');
$validator = new Dartcafe\EmailValidator\EmailValidator(lists: $lists);
```

Each list contributes a **ListOutcome** entry to `ValidationResult::getLists()`.
If a `deny` list matches, a **warning** like `deny_list:<name>` is added (it does not flip `sendable` by default).

---

## Domain suggestions

A simple text-based provider ships as default (common mailbox providers & typos).
You can provide your own by implementing:

```php
namespace Dartcafe\EmailValidator\Contracts;

interface DomainSuggestionProvider {
    public function suggestDomain(string $asciiLowerDomain): ?string;
}
```

Pass your provider into the constructor:

```php
$validator = new EmailValidator($mySuggestionProvider, $lists);
```

---

## REST endpoint & OpenAPI

The package includes OpenAPI attribute definitions (`docs/`) and a generator script can produce `public/openapi.json`.
A minimal demo app with a UI and import/export of lists lives in the companion project (see below).

Generate the spec:

```bash
# if you have zircote/swagger-php installed (dev)
vendor/bin/openapi --bootstrap vendor/autoload.php --format json --output public/openapi.json docs
```

Endpoints (as defined in the spec):
- `POST /validate` — `{ "email": "user@example.com" }`
- `GET  /validate?email=user@example.com`

> If you prefer a ready-to-run server + UI, use the **demo** project below.

---

## Demo (optional)

A separate demo app provides:
- a pretty **checklist UI** for results,
- a **lists editor** (INI + text files) with **import/export (ZIP/JSON)**,
- and an embedded Swagger UI.

Repo: `dartcafe/email-validator-demo` (coming soon).

---

## Development

### Scripts
```bash
# coding standards (dry-run / fix)
composer cs
composer cs:fix

# static analysis
vendor/bin/psalm --no-cache

# tests
composer test
```

### Contributing
PRs and issues are welcome. Please run CS, Psalm, and tests before submitting.

---

## License

See [LICENSE](./LICENSE).