<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

use Dartcafe\EmailValidator\Value\ValidationResult;

interface IValidator
{
    /**
     * Validates an email address and returns a structured result.
     *
     * @param string $emailAddress The email address to validate.
     * @return ValidationResult The result of the validation.
     */
    public function validate(string $emailAddress): ValidationResult;
}
