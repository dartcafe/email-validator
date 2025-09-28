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

    /**
     * Load list configurations from an INI file and create a ListManager with corresponding checkers.
     *
     * @param string $iniPath Path to the INI configuration file
     * @return self
     */
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
     * Evaluate all lists against the given normalized email address and domain.
     *
     * @param string $normalizedAddress Full normalized email address (lowercased)
     * @param string $normalizedDomain  Normalized domain part (lowercased)
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
