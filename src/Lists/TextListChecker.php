<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Lists;

use Dartcafe\EmailValidator\Value\ListOutcome;

/**
 * Text-file based list checker. One entry per line.
 * For checkType=domain: exact domain match.
 * For checkType=address: case-insensitive full address match.
 */
final class TextListChecker implements ListChecker
{
    private ListConfig $cfg;
    /** @var array<string, true> */
    private array $set;

    /**
     * @param ListConfig $cfg Configuration for this list
     */
    public function __construct(ListConfig $cfg)
    {
        $this->cfg = $cfg;
        $this->set = $this->loadFile($cfg->listFile);
    }

    /**
     * Load the list file into a set (associative array for O(1) lookups).
     * Lines starting with # are comments and ignored.
     * Empty lines are ignored.
     * Lines are trimmed and lowercased.
     * Entries are keys in the returned array with value true.
     * If the file cannot be read, an empty set is returned.
     *
     * @param string $path
     * @return array<string,true>
     */
    private function loadFile(string $path): array
    {
        $set = [];
        if (!is_file($path)) {
            return $set;
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return $set;
        }

        while (($line = fgets($fh)) !== false) {
            // strip UTF-8 BOM if present
            if (str_starts_with($line, "\xEF\xBB\xBF")) {
                $line = substr($line, 3);
            }

            $hash = strpos($line, '#');
            if ($hash !== false) {
                $line = substr($line, 0, $hash);
            }

            $entry = strtolower(trim($line));
            if ($entry === '') {
                continue;
            }

            $set[$entry] = true;
        }
        fclose($fh);
        return $set;
    }

    /** Evaluate the given normalized address and domain against the list.
     *
     * @param string $normalizedAddress Lowercased full email address
     * @param string $normalizedDomain Lowercased IDNA ASCII domain
     * @return ListOutcome
     */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): ListOutcome
    {
        $matched = false;
        $matchedValue = null;

        if ($this->cfg->checkType === 'domain') {
            // exact domain match; could be extended to suffix/patterns later
            if ($normalizedDomain !== '' && isset($this->set[$normalizedDomain])) {
                $matched = true;
                $matchedValue = $normalizedDomain;
            }
        } else { // address
            if ($normalizedAddress !== '' && isset($this->set[strtolower($normalizedAddress)])) {
                $matched = true;
                $matchedValue = $normalizedAddress;
            }
        }

        return new ListOutcome(
            $this->cfg->listName,
            $this->cfg->humanName,
            $this->cfg->type,
            $this->cfg->checkType,
            $matched,
            $matchedValue,
        );
    }
}
