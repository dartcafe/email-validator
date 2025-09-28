<?php
declare(strict_types=1);

namespace Dartcafe\EmailValidator\Adapter;

use Dartcafe\EmailValidator\Contracts\ListProvider;
use Dartcafe\EmailValidator\Value\ListOutcome;

/**
 * INI-driven list provider reading plain text files.
 *
 * INI sections:
 *   type         = "allow" | "deny"
 *   listFileName = "lists/disposable_domains.txt"
 *   checkType    = "domain" | "address"
 *   listName     = "deny_disposable"
 *   humanName    = "Disposable domains"
 *
 * List files: 1 entry per line, '#' for comments, case-insensitive compare.
 */
final class IniListProvider implements ListProvider
{
    /** @var list<array{type:string,checkType:string,name:string,humanName:string,values:list<string>}> */
    private array $rules;

    /**
     * @param list<array{type:string,checkType:string,name:string,humanName:string,values:list<string>}> $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Factory reading INI + list files.
     */
    public static function fromFile(string $iniPath): self
    {
        $real = \realpath($iniPath) ?: $iniPath;
        if (!\is_file($real)) {
            throw new \RuntimeException('INI file not found: ' . $iniPath);
        }

        $sections = \parse_ini_file($real, true, \INI_SCANNER_TYPED);
        if ($sections === false) {
            throw new \RuntimeException('Invalid INI file: ' . $iniPath);
        }

        $baseDir = \dirname($real);
        $rules = [];

        foreach ($sections as $name => $data) {
            if (!\is_array($data)) {
                continue;
            }

            $type       = isset($data['type']) ? (string)$data['type'] : '';
            $checkType  = isset($data['checkType']) ? (string)$data['checkType'] : '';
            $listName   = isset($data['listName']) ? (string)$data['listName'] : $name;
            $humanName  = isset($data['humanName']) ? (string)$data['humanName'] : $listName;
            $listFile   = isset($data['listFileName']) ? (string)$data['listFileName'] : '';

            if ($type === '' || $checkType === '' || $listFile === '') {
                // skip invalid section
                continue;
            }

            $values = self::readListFile($baseDir, $listFile);
            $rules[] = [
                'type'      => $type,          // allow | deny
                'checkType' => $checkType,    // domain | address
                'name'      => $listName,
                'humanName' => $humanName,
                'values'    => $values,
            ];
        }

        return new self($rules);
    }

    /**
     * @return list<string>
     */
    private static function readListFile(string $baseDir, string $relativePath): array
    {
        $path = $relativePath;
        if (!\preg_match('~^(/|[A-Za-z]:[\\\\/])~', $relativePath)) {
            $path = $baseDir . DIRECTORY_SEPARATOR . $relativePath;
        }
        $real = \realpath($path) ?: $path;
        if (!\is_file($real)) {
            // non-fatal: treat as empty
            return [];
        }

        $raw = @\file_get_contents($real);
        if ($raw === false) {
            return [];
        }

        $out = [];
        $seen = [];
        $lines = \preg_split('~\R~', $raw);
        if ($lines === false) { $lines = []; }

        foreach ($lines as $line) {
            $line = \preg_replace('~#.*$~', '', $line) ?? $line; // strip comments
            $line = \trim($line);
            if ($line === '') { continue; }
            $key = \mb_strtolower($line);
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;
            $out[] = $line; // keep original spelling for matchedValue
        }

        // stable order
        \usort($out, static fn(string $a, string $b) =>
            \strcasecmp($a, $b)
        );

        return $out;
    }

    /** @inheritDoc */
    public function evaluate(string $normalizedAddress, string $normalizedDomain): array
    {
        $addrLC = \mb_strtolower($normalizedAddress);
        $domLC  = \mb_strtolower($normalizedDomain);

        $out = [];
        foreach ($this->rules as $r) {
            $matched = false;
            $matchedValue = null;

            if ($r['checkType'] === 'domain') {
                foreach ($r['values'] as $v) {
                    if ($domLC === \mb_strtolower($v)) {
                        $matched = true;
                        $matchedValue = $v;
                        break;
                    }
                }
            } else { // address
                foreach ($r['values'] as $v) {
                    if ($addrLC === \mb_strtolower($v)) {
                        $matched = true;
                        $matchedValue = $v;
                        break;
                    }
                }
            }

            $out[] = new ListOutcome(
                name: $r['name'],
                humanName: $r['humanName'],
                type: $r['type'],
                checkType: $r['checkType'],
                matched: $matched,
                matchedValue: $matchedValue
            );
        }

        return $out;
    }
}
