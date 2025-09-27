<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\ValidationResult;

interface IValidator
{
    /**
     * Validates an email address and returns a structured result.
     */
    public function validate(string $emailAddress): ValidationResult;
}
