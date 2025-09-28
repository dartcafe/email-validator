<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

use Dartcafe\EmailValidator\Contracts\IDomainSuggestionProvider;

/**
 * Suggestion provider based on a simple text file with "typo,correct" lines.
 *
 * Lines starting with '#' are comments and ignored.
 * Inline comments (after '#') are also ignored.
 * Blank lines are ignored.
 *
 * Example content:
 * ```
 * # common typos
 * gmal.com, gmail.com
 * hotmial.com, hotmail.com
 * yaho.com, yahoo.com
 * ```
 *
 * Domains are case-insensitive and always lowercased internally.
 */
final class TextDomainSuggestionProvider implements IDomainSuggestionProvider
{
    /** @var array<string,string> */
    private array $map;

    /**
     * @param array<string,string> $map
     */
    private function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * Load the default suggestion list from the built-in resource file.
     * @return self
     */
    public static function default(): self
    {
        $path = __DIR__ . '/../resources/domain_suggestions.txt';
        return self::fromFile($path);
    }

    /**
     * Load suggestion map from a text file.
     *
     * @param string $path Path to the suggestion file
     * @return self
     */
    public static function fromFile(string $path): self
    {
        $map = [];

        if (!is_file($path)) {
            // keep empty map if file does not exist
            return new self($map);
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return new self($map);
        }

        while (($line = fgets($fh)) !== false) {
            // strip BOM on first line
            if (str_starts_with($line, "\xEF\xBB\xBF")) {
                $line = substr($line, 3);
            }

            // remove inline comments starting with '#'
            $hashPos = strpos($line, '#');
            if ($hashPos !== false) {
                $line = substr($line, 0, $hashPos);
            }

            $line = trim($line);
            if ($line === '') {
                continue; // skip empty
            }

            // expected format: typo,correct
            $parts = array_map('trim', explode(',', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                continue; // skip invalid line
            }

            $typo = strtolower($parts[0]);
            $correct = strtolower($parts[1]);

            // basic guard: ignore self-maps
            if ($typo === $correct) {
                continue;
            }

            $map[$typo] = $correct;
        }

        fclose($fh);

        return new self($map);
    }

    /**
     * Suggest a correction for the given domain, or null if no suggestion.
     *
     * @param string $domain Lowercased, IDNA-ASCII domain (may be empty)
     * @return null|string Suggested replacement domain (lowercased, IDNA-ASCII)
     */
    public function suggestDomain(string $domain): ?string
    {
        // inputs are assumed lowercased; normalize just in case
        $domain = strtolower($domain);
        return $this->map[$domain] ?? null;
    }
}
