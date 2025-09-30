<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Tests;

use Dartcafe\EmailValidator\Adapter\IniListProvider;
use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Tests\Support\TempFs;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class IniListProviderTest extends TestCase
{
    use TempFs;

    public function testDenyAddressMatch(): void
    {
        $ini = <<<INI
[deny_banned]
type = deny
checkType = address
listFileName = blacklists/banned.txt
listName = banned
humanName = Banned addresses
INI;

        $iniPath = $this->tmpFile('lists.ini', $ini);
        $this->tmpFile('blacklists/banned.txt', "ceo@example.com\nvip@example.com\n");

        // Build sections as IniListProvider expects them
        $sections = $this->buildSections($iniPath);

        $provider  = new IniListProvider($sections);
        $validator = new EmailValidator(null, $provider);

        $res = $validator->validate('ceo@example.com');

        // Warning for deny-list (informational)
        $this->assertContains('deny_list:banned', $res->getWarnings());

        // find "banned" outcome
        $found = null;
        foreach ($res->getLists() as $o) {
            if ($o->name === 'banned') {
                $found = $o;
                break;
            }
        }
        $this->assertNotNull($found, 'Outcome "banned" not found');
        $this->assertTrue($found->matched);
        $this->assertSame('ceo@example.com', $found->matchedValue);
    }

    /**
     * Parse INI + load list files to build the sections array as IniListProvider expects it.
     * @return list<array{type:string,checkType:string,name:string,humanName:string,values:list<string>}>
     */
    private function buildSections(string $iniPath): array
    {
        $base = \dirname($iniPath);
        $raw  = @\parse_ini_file($iniPath, true, \INI_SCANNER_TYPED);
        if (!\is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $section => $kv) {
            if (!\is_array($kv)) {
                continue;
            }
            $m = \array_change_key_case($kv, \CASE_LOWER);

            $type      = (string)($m['type'] ?? '');
            $checkType = (string)($m['checktype'] ?? '');
            $name      = (string)($m['listname'] ?? $section);
            $human     = (string)($m['humanname'] ?? $section);
            $file      = (string)($m['listfilename'] ?? '');

            $values = [];
            if ($file !== '') {
                // Resolve relatively to INI file
                $rel  = str_replace(['\\','/'], \DIRECTORY_SEPARATOR, $file);
                if (\str_starts_with($rel, 'config' . \DIRECTORY_SEPARATOR)) {
                    $rel = \substr($rel, 7); // "config/" kappen
                }
                $full = $base . \DIRECTORY_SEPARATOR . ltrim($rel, \DIRECTORY_SEPARATOR);
                if (\is_file($full)) {
                    $lines = \preg_split('/\R/u', (string)\file_get_contents($full)) ?: [];
                    foreach ($lines as $ln) {
                        $ln = \trim($ln);
                        if ($ln === '' || $ln[0] === '#') {
                            continue;
                        }
                        $values[] = \strtolower($ln);
                    }
                }
            }

            /** @var list<string> $values */
            $values = \array_values(\array_unique($values));

            $out[] = [
                'type'      => $type,
                'checkType' => $checkType,
                'name'      => $name,
                'humanName' => $human,
                'values'    => $values,
            ];
        }

        /** @var list<array{type:string,checkType:string,name:string,humanName:string,values:list<string>}> $out */
        return $out;
    }
}
