# Email Validator for PHP

A small, framework‑agnostic PHP library to validate email addresses with.

Separates **format validity** from **deliverability**, supports **domain typo suggestions**, and lets you plug in custom **providers** for lists and DNS. File/INI list handling is offered via a small adapter.

---

# Features

- **Format-only validity** (`valid`) — syntax & RFC length checks plus basic domain shape.
- **Deliverability prediction** (`sendable`) — DNS: domain existence + MX records (via pluggable resolver).
- **Domain suggestions** with typo‑distance scoring (Levenshtein or Damerau–Levenshtein)
- **Pluggable list checks** — allow/deny by domain or full address via a `ListProvider`.
- **Clear result model** — typed getters for PHP and compact JSON for APIs.
- A clean, typed **`ValidationResult`** DTO and an optional **OpenAPI** spec for a tiny REST endpoint
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
use Dartcafe\EmailValidator\Suggestion\TextDomainSuggestionProvider;

// Create a validator with default providers
$validator = new EmailValidator(
    suggestions: TextDomainSuggestionProvider::default() // common domains bundled
);

// Validate an address
$result = $validator->validate('ceo@gamil.com');

// Access the structured result
$result->isValid();             // format-only validity (bool)
$result->isSendable();          // domain has A/AAAA and MX (bool)
$result->getReasons();          // list<string> format/DNS reasons
$result->getWarnings();         // list<string> (e.g. deny_list:<name>)
$result->getNormalized();       // ascii-lower-cased domain, same local part
$result->getSuggestion();       // suggested corrected address or null
$result->getSuggestionScore();  // 0.0–1.0 confidence score or null
$result->getDomainExists();     // ?bool
$result->getHasMx();            // ?bool
$result->getLists();            // list<ListOutcome>
```

Example JSON (via `json_encode($result)`):

```json
{
  "query": "ceo@gamil.com",
  "corrections": {
    "normalized": "ceo@gmail.com",
    "suggestion": "ceo@gmail.com",
    "suggestionScore": 0.92
  },
  "simpleResults": {
    "formatValid": true,
    "isSendable": true,
    "hasWarnings": false
  },
  "reasons": [],
  "warnings": [],
  "dns": { "domainExists": true, "hasMx": true },
  "lists": []
}
```

---

## Domain suggestions & distance metrics

By default the validator uses a curated set of popular domains and **Levenshtein** distance.
You can provide your own list and choose **Damerau–Levenshtein**:

```php
use Dartcafe\EmailValidator\Suggestion\ArrayDomainSuggestionProvider;
use Dartcafe\EmailValidator\Suggestion\Distance;

// Provide your own candidate domains:
$domains = ['gmail.com', 'yahoo.com', 'outlook.com'];

// Choose the metric (LEVENSHTEIN | DAMERAU_LEVENSHTEIN)
$suggestions = ArrayDomainSuggestionProvider::fromArray($domains, Distance::DAMERAU_LEVENSHTEIN);

$validator = new EmailValidator(suggestions: $suggestions);
$res = $validator->validate('user@gmil.com');

$res->getSuggestion();       // "user@gmail.com"
$res->getSuggestionScore();  // e.g. 0.93
```

The **score** is a normalized similarity in **[0.0, 1.0]**
(1.0 = identical, ~0.9 strong typo‑fix candidate, <0.5 usually weak).

---

## Deliverability (DNS)

Deliverability is **heuristic**: the validator checks MX first, then A/AAAA fallback.

- `isSendable()` is `true` only if **domain resolves** *and* **MX exists**.
- `reasons` may contain `domain_not_found` or `no_mx`.

Custom DNS is possible by implementing:

```php
namespace Dartcafe\EmailValidator\Contracts;

interface DnsResolver
{
    /** @return array{0:?bool,1:?bool} [domainExists, hasMx] */
    public function check(string $asciiLowerDomain): array;
}
```

and passing it into the validator’s constructor.

---

## Lists: allow/deny with a pluggable provider

The library defines a minimal interface:

```php
namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\ListOutcome;

interface ListProvider
{
    /**
     * @return list<ListOutcome>
     */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): array;
}
```

Return `ListOutcome` objects (type: `allow|deny`, `checkType: address|domain`, `matched`, …).
**Deny matches** are reported as **warnings** (do **not** change `isSendable()`).

You can write your own provider (DB/file/memory).
A lightweight INI/Text adapter is available in the demo; production apps usually inject their own provider.

---

## REST endpoint (optional)

The package ships an **OpenAPI** description (`public/openapi.json`) for a tiny REST API:

- `GET /validate?email=...`
- `POST /validate` with `{"email": "..." }`
- `GET /health`

You can wire these routes in any micro-router or reuse the demo app.

---

## OpenAPI

- File: `public/openapi.json`
- Version in spec is kept in sync with releases via `docs/OpenApiConfig.php` and the release script.

Generate (in the lib):

```bash
composer run openapi:generate
```

---

## Types & DTOs

- `Dartcafe\EmailValidator\Value\ValidationResult` (mutable, JSON‑serializable)
- `Dartcafe\EmailValidator\Value\ListOutcome`
- Suggestion types:
  - `Dartcafe\EmailValidator\Value\SuggestedDomain` (domain + score)

Everything is annotated for Psalm and IDEs.

---

## Quality

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

MIT © René Gieling

See [LICENSE](./LICENSE).