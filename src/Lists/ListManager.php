<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Lists;

use Dartcafe\EmailValidator\Value\ListOutcome;

/**
 * Orchestrates multiple list checkers loaded from an INI config.
 */
final class ListManager
{
    /** @var list<ListChecker> */
    private array $checkers = [];

    /**
     * @param list<ListChecker> $checkers
     */
    public function __construct(array $checkers)
    {
        $this->checkers = $checkers;
    }

    public static function fromIni(string $iniPath): self
    {
        $configs = ListConfig::fromIni($iniPath);
        $checkers = [];
        foreach ($configs as $cfg) {
            $checkers[] = new TextListChecker($cfg);
        }
        return new self($checkers);
    }

    /**
     * @return list<ListOutcome> $out
     */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): array
    {
        $out = [];
        foreach ($this->checkers as $checker) {
            $out[] = $checker->evaluate($normalizedAddress, $normalizedDomain);
        }
        return $out;
    }
}
