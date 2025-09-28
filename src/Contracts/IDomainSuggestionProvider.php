<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

interface IDomainSuggestionProvider
{
    /**
     * Suggest a replacement domain for a possibly misspelled domain.
     *
     * @param string $domain Lowercased, IDNA-ASCII domain (may be empty)
     * @return null|string   Suggested replacement domain (lowercased, IDNA-ASCII)
     */
    public function suggestDomain(string $domain): ?string;
}
