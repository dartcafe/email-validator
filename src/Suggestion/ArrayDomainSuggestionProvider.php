<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\Contracts\ScoredDomainSuggestionProvider;
use Dartcafe\EmailValidator\Value\SuggestedDomain;

final class ArrayDomainSuggestionProvider implements ScoredDomainSuggestionProvider
{
    /** @var list<string> */
    private array $domains = [];

    private ?DomainSuggestionProvider $fallback;

    /**
     * @param iterable<mixed> $domains
     */
    public function __construct(iterable $domains, ?DomainSuggestionProvider $fallback = null)
    {
        /** @var list<string> $list */
        $list = self::normalize($domains);
        $this->domains  = $list;
        $this->fallback = $fallback;
    }

    /** @param iterable<mixed> $domains @return list<string> */
    private static function normalize(iterable $domains): array
    {
        $out = [];
        foreach ($domains as $d) {
            $s = strtolower(trim((string)$d));
            if ($s !== '' && $s[0] !== '#') {
                $out[] = $s;
            }
        }
        /** @var list<string> $unique */
        $unique = array_values(array_unique($out));
        return $unique;
    }

    public function suggestDomain(string $domain): ?string
    {
        return $this->suggestDomainScored($domain)?->domain;
    }

    public function suggestDomainScored(string $domain): ?SuggestedDomain
    {
        $needle = strtolower($domain);
        if (\function_exists('idn_to_ascii')) {
            $ascii  = \idn_to_ascii($needle, 0);
            $needle = $ascii !== false ? $ascii : $needle;
        }
        if ($needle === '' || $this->domains === []) {
            return ($this->fallback instanceof ScoredDomainSuggestionProvider)
                ? $this->fallback->suggestDomainScored($domain)
                : null;
        }

        $best = null;
        $bestDist = PHP_INT_MAX;
        foreach ($this->domains as $cand) {
            $dist = \levenshtein($needle, $cand);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $cand;
                if ($bestDist === 0) {
                    break;
                }
            }
        }

        // same acceptance threshold as before (length-dependent)
        $len = max(\strlen($needle), 1);
        $threshold = ($len <= 5) ? 1 : (($len <= 10) ? 2 : 3);

        if ($best === null || $bestDist > $threshold) {
            return ($this->fallback instanceof ScoredDomainSuggestionProvider)
                ? $this->fallback->suggestDomainScored($domain)
                : null;
        }

        // normalized score: 1 - (dist / max(len))
        $den = max(\strlen($needle), \strlen($best), 1);
        $score = 1.0 - ($bestDist / $den);
        $score = max(0.0, min(1.0, $score));

        return new SuggestedDomain($best, $score);
    }
}
