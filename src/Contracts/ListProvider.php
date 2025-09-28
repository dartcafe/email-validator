<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\ListOutcome;

/**
 * Provides allow/deny list evaluations for an email.
 */
interface ListProvider
{
    /**
     * @return list<ListOutcome>
     */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): array;
}
