<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

/**
 * Distance metric identifiers.
 */
final class Distance
{
    public const LEVENSHTEIN          = 'levenshtein';
    // Damenrau-Levenshtein (Optimal String Alignment; allows only adjacent transpositions)
    public const DAMERAU_LEVENSHTEIN  = 'damerau';

    /** @psalm-pure */
    public static function isValid(string $metric): bool
    {
        return \in_array($metric, [self::LEVENSHTEIN, self::DAMERAU_LEVENSHTEIN], true);
    }
}
