<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\SuggestedDomain;

/**
 * Optional extension: providers may return a scored suggestion.
 * Implementers should still support suggestDomain() from DomainSuggestionProvider.
 */
interface ScoredDomainSuggestionProvider extends DomainSuggestionProvider
{
    public function suggestDomainScored(string $domain): ?SuggestedDomain;
}
