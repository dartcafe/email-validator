<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Lists;

final class ListConfig
{
    public function __construct(
        public readonly string $typ,       // allow|deny
        public readonly string $listFile,  // resolved absolute/normalized path
        public readonly string $checkType, // domain|address
        public readonly string $listName,  // machine name
        public readonly string $humanName,  // display
    ) {
    }

    /**
     * @return list<self>
     */
    public static function fromIni(string $iniPath): array
    {
        /** @var array<string, array<string, mixed>>|false $sections */
        $sections = parse_ini_file($iniPath, true, INI_SCANNER_TYPED);
        if ($sections === false) {
            return [];
        }

        $baseDir = \dirname($iniPath);
        $out = [];

        foreach ($sections as $data) {
            $typ       = \strtolower((string)($data['typ'] ?? ''));
            $rawFile   = (string)($data['listFileName'] ?? '');
            $checkType = \strtolower((string)($data['checkType'] ?? ''));
            $listName  = (string)($data['listName'] ?? '');
            $humanName = (string)($data['humanName'] ?? '');

            if ($typ === '' || $rawFile === '' || $checkType === '' || $listName === '' || $humanName === '') {
                continue;
            }
            if (!\in_array($typ, ['allow','deny'], true)) {
                continue;
            }
            if (!\in_array($checkType, ['domain','address'], true)) {
                continue;
            }

            $resolved = self::resolvePath($baseDir, $rawFile);
            $out[] = new self($typ, $resolved, $checkType, $listName, $humanName);
        }

        return $out;
    }

    /**
     * Resolve a list file path relative to the INI directory.
     * Accepts absolute paths; for relative, joins with $baseDir.
     * Also tolerates a leading "config/" if the INI already lives in ".../config".
     */
    private static function resolvePath(string $baseDir, string $ref): string
    {
        $ref = \str_replace(['/', '\\'], DIRECTORY_SEPARATOR, \trim($ref));
        if ($ref === '') {
            return $ref;
        }

        // absolute? (/unix or C:\win)
        if (\preg_match('~^([A-Za-z]:\\\\|/)~', $ref) === 1) {
            return $ref;
        }

        // tolerate "config/..." when INI lives in ".../config"
        $baseName = \strtolower(\basename($baseDir));
        if ($baseName === 'config' && \str_starts_with(\strtolower($ref), 'config' . DIRECTORY_SEPARATOR)) {
            $ref = \substr($ref, \strlen('config' . DIRECTORY_SEPARATOR));
        }

        $full = \rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . \ltrim($ref, DIRECTORY_SEPARATOR);
        $real = \realpath($full);

        return $real !== false ? $real : $full; // if not yet existing, keep joined path
    }
}
