<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Tests\Support;

trait TempFs
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/ev_' . bin2hex(random_bytes(5));
        @mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    protected function tmpFile(string $rel, string $content): string
    {
        $path = $this->tmpDir . '/' . ltrim($rel, '/');
        @mkdir(\dirname($path), 0777, true);
        file_put_contents($path, $content);
        return $path;
    }
}
