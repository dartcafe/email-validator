<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Lists;

use Dartcafe\EmailValidator\Value\ListOutcome;

interface ListChecker
{
    /**
     * Evaluate this list for the given normalized inputs.
     *
     * @param string $normalizedAddress lowercased, IDNA-ASCII domain part
     * @param string $normalizedDomain  lowercased, IDNA-ASCII
     * @return ListOutcome
     */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): ListOutcome;
}
