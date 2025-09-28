<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Adapter\IniListProvider;
use PHPUnit\Framework\TestCase;

final class ListValidationTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ev_lists_' . bin2hex(random_bytes(4));
        $this->mkdir($this->tmpDir . '/blacklists');
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tmpDir);
    }

    // some utils

    private function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Cannot create dir: ' . $dir);
        }
    }

    /**
     * @param array<string, array<string, scalar>> $sections
     * @return string path to created INI file
     */
    private function createIni(array $sections): string
    {
        $ini = [];
        foreach ($sections as $name => $kv) {
            $ini[] = "[$name]";
            foreach ($kv as $k => $v) {
                $ini[] = $k . ' = ' . (is_bool($v) ? ($v ? 'true' : 'false') : (string)$v);
            }
            $ini[] = '';
        }
        $path = $this->tmpDir . '/lists.ini';
        $this->mkdir(dirname($path));
        file_put_contents($path, implode("\n", $ini));
        return $path;
    }

    private function write(string $rel, string $content): string
    {
        $path = $this->tmpDir . '/' . ltrim($rel, '/');
        $this->mkdir(dirname($path));
        file_put_contents($path, $content);
        return $path;
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $e;
            if (is_dir($p) && !is_link($p)) {
                $this->rmdirRecursive($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    // the tests

    public function testDenyDomainAddsWarning(): void
    {
        $disposable = $this->write('blacklists/disposable.txt', "mailinator.com\n");

        $ini = $this->createIni([
            'deny_disposable' => [
                'type'         => 'deny',
                'listFileName' => '"' . $disposable . '"',
                'checkType'    => 'domain',
                'listName'     => 'disposable',
                'humanName'    => '"Disposable providers"',
            ],
        ]);

        $lists = IniListProvider::fromFile($ini);
        $v = new EmailValidator(lists: $lists);

        $res = $v->validate('bob@mailinator.com');

        $this->assertTrue($res->isValid(), 'deny-list must not impact format validity');
        $this->assertContains('deny_list:disposable', $res->getWarnings());

        // Ensure the outcomes reflect the match
        $match = array_values(array_filter(
            $res->getLists(),
            fn ($o) => $o->name === 'disposable',
        ))[0] ?? null;
        $this->assertNotNull($match);
        $this->assertTrue($match->matched);
        $this->assertSame('deny', $match->type);
        $this->assertSame('domain', $match->checkType);
    }

    public function testDenyAddressAddsWarning(): void
    {
        $banned = $this->write('blacklists/banned.txt', "ceo@example.com\n");

        $ini = $this->createIni([
            'deny_banned' => [
                'type'         => 'deny',
                'listFileName' => '"' . $banned . '"',
                'checkType'    => 'address',
                'listName'     => 'banned_addresses',
                'humanName'    => '"Banned recipients"',
            ],
        ]);

        $lists = IniListProvider::fromFile($ini);
        $v = new EmailValidator(lists: $lists);

        $res = $v->validate('ceo@example.com');

        $this->assertTrue($res->isValid());
        $this->assertContains('deny_list:banned_addresses', $res->getWarnings());
    }

    public function testAllowListDoesNotCreateWarning(): void
    {
        $vip = $this->write('blacklists/vip.txt', "vip.customer@gmail.com\n");

        $ini = $this->createIni([
            'allow_vip' => [
                'type'         => 'allow',
                'listFileName' => '"' . $vip . '"',
                'checkType'    => 'address',
                'listName'     => 'vip',
                'humanName'    => '"VIP allowlist"',
            ],
        ]);

        $lists = IniListProvider::fromFile($ini);
        $v = new EmailValidator(lists: $lists);

        $res = $v->validate('vip.customer@gmail.com');

        $this->assertTrue($res->isValid());
        $this->assertSame([], $res->getWarnings(), 'allow list must not create warnings');
    }

    public function testMissingListFilesAreHandled(): void
    {
        $ini = $this->createIni([
            'deny_missing' => [
                'type'         => 'deny',
                'listFileName' => '"' . $this->tmpDir . '/blacklists/does-not-exist.txt' . '"',
                'checkType'    => 'domain',
                'listName'     => 'missing',
                'humanName'    => '"Missing file"',
            ],
        ]);

        $lists = IniListProvider::fromFile($ini);
        $v = new EmailValidator(lists: $lists);

        $res = $v->validate('user@example.com');

        $this->assertTrue($res->isValid());
        $match = array_values(array_filter(
            $res->getLists(),
            fn ($o) => $o->name === 'missing',
        ))[0] ?? null;
        $this->assertNotNull($match);
        $this->assertFalse($match->matched);
    }

    public function testCaseInsensitivity(): void
    {
        $disposable = $this->write('blacklists/disposable.txt', "MailInator.com\n");
        $ini = $this->createIni([
            'deny_disposable' => [
                'type'         => 'deny',
                'listFileName' => '"' . $disposable . '"',
                'checkType'    => 'domain',
                'listName'     => 'disposable',
                'humanName'    => '"Disposable providers"',
            ],
        ]);

        $lists = IniListProvider::fromFile($ini);
        $v = new EmailValidator(lists: $lists);

        $res = $v->validate('USER@MAILINATOR.COM');

        $this->assertTrue($res->isValid());
        $this->assertContains('deny_list:disposable', $res->getWarnings());
    }
}
