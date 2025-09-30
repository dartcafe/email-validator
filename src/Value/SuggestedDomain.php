<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Value;

/**
 * Scored suggestion for a replacement domain.
 * $score is in [0.0, 1.0], higher = more likely typo/fix.
 */
final class SuggestedDomain implements \JsonSerializable
{
    public function __construct(
        public readonly string $domain,
        public readonly float $score,
    ) {
    }

    /** @return array{domain:string,score:float} */
    public function jsonSerialize(): array
    {
        return ['domain' => $this->domain, 'score' => $this->score];
    }
}
