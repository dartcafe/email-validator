<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\Contracts\ScoredDomainSuggestionProvider;
use Dartcafe\EmailValidator\Value\SuggestedDomain;

/**
 * Default suggestion provider that wraps an engine (ArrayDomainSuggestionProvider by default).
 */
final class TextDomainSuggestionProvider implements DomainSuggestionProvider, ScoredDomainSuggestionProvider
{
    private DomainSuggestionProvider $engine;
    private string $metric;

    private function __construct(DomainSuggestionProvider $engine, string $metric)
    {
        $this->engine  = $engine;
        $this->metric  = Distance::isValid($metric) ? $metric : Distance::LEVENSHTEIN;
    }

    public static function default(string $metric = Distance::LEVENSHTEIN): self
    {
        // keep your default list here
        $defaults = [
            'gmail.com','yahoo.com','outlook.com','hotmail.com','live.com',
            'icloud.com','gmx.de','web.de','t-online.de','proton.me',
        ];
        $engine = new ArrayDomainSuggestionProvider($defaults, null, $metric);
        return new self($engine, $metric);
    }

    /**
     * Convenience to build from a custom list
     * @psalm-suppress PossiblyUnusedMethod */
    public static function fromArray(iterable $domains, string $metric = Distance::LEVENSHTEIN): self
    {
        $engine = new ArrayDomainSuggestionProvider($domains, null, $metric);
        return new self($engine, $metric);
    }

    public function suggestDomain(string $domain): ?string
    {
        return $this->engine->suggestDomain($domain);
    }

    public function suggestDomainScored(string $domain): ?SuggestedDomain
    {
        if ($this->engine instanceof ScoredDomainSuggestionProvider) {
            return $this->engine->suggestDomainScored($domain);
        }
        $s = $this->engine->suggestDomain($domain);
        if ($s === null) {
            return null;
        }

        // fallback score from edit distance
        $needle = strtolower($domain);
        if (\function_exists('idn_to_ascii')) {
            $ascii  = \idn_to_ascii($needle, 0);
            $needle = $ascii !== false ? $ascii : $needle;
        }
        $best  = \strtolower($s);
        $score = StringDistance::normalizedScore($needle, $best, $this->metric);
        return new SuggestedDomain($s, $score);
    }
}
