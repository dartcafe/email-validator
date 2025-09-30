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

    private function __construct(DomainSuggestionProvider $engine)
    {
        $this->engine = $engine;
    }

    public static function default(): self
    {
        // keep your default list here
        $defaults = [
            'gmail.com','yahoo.com','outlook.com','hotmail.com','live.com',
            'icloud.com','gmx.de','web.de','t-online.de','proton.me',
        ];
        return new self(new ArrayDomainSuggestionProvider($defaults));
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
        $best = strtolower($s);
        $dist = \levenshtein($needle, $best);
        $den  = max(\strlen($needle), \strlen($best), 1);
        $score = 1.0 - ($dist / $den);
        $score = max(0.0, min(1.0, $score));
        return new SuggestedDomain($s, $score);
    }
}
