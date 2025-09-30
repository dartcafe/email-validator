<?php
declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;

final class ArrayDomainSuggestionProvider implements DomainSuggestionProvider
{
    /** @var list<string> */
    private array $domains = [];
    private ?DomainSuggestionProvider $fallback;

    /**
     * @param iterable<mixed> $domains  list of domains (lowercase preferred)
     */
    public function __construct(iterable $domains, ?DomainSuggestionProvider $fallback = null)
    {
        $this->domains  = self::normalize($domains);
        $this->fallback = $fallback;
    }

    /**
     * @param iterable<mixed> $domains
     * @return list<string>
     */
    private static function normalize(iterable $domains): array
    {
        $out = [];
        foreach ($domains as $d) {
            $s = strtolower(trim((string)$d));
            if ($s !== '' && $s[0] !== '#') { $out[] = $s; }
        }
        /** @var list<string> $uniq */
        $uniq = array_values(array_unique($out));
        return $uniq;
    }

    public function suggestDomain(string $domain): ?string
    {
        $needle = strtolower($domain);
        if (\function_exists('idn_to_ascii')) {
            $ascii  = \idn_to_ascii($needle, 0);
            $needle = $ascii !== false ? $ascii : $needle;
        }
        if ($needle === '' || $this->domains === []) {
            return $this->fallback?->suggestDomain($domain);
        }

        $best = null; $bestDist = PHP_INT_MAX;
        foreach ($this->domains as $cand) {
            $dist = \levenshtein($needle, $cand);
            if ($dist < $bestDist) {
                $bestDist = $dist; $best = $cand;
                if ($bestDist === 0) break;
            }
        }
        $len = max(\strlen($needle), 1);
        $threshold = ($len <= 5) ? 1 : (($len <= 10) ? 2 : 3);

        return ($best !== null && $bestDist <= $threshold)
            ? $best
            : $this->fallback?->suggestDomain($domain);
    }
}
