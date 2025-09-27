<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\EmailValidator;
use PHPUnit\Framework\TestCase;

final class EmailValidatorIdnTest extends TestCase
{
    private function requireIdn(): void
    {
        if (!function_exists('idn_to_ascii')) {
            $this->markTestSkipped('intl extension (idn_to_ascii) not available');
        }
    }

    /**
     * Split mailbox into local and domain; fails the test if '@' is missing.
     * @return array{0: non-empty-string, 1: non-empty-string}
     */
    private function splitMailbox(string $input): array
    {
        $at = strpos($input, '@');
        if ($at === false) {
            $this->fail('Test input must contain "@"');
        }

        $local = substr($input, 0, $at);
        $domain = substr($input, $at + 1);

        if ($local === '' || $domain === '') {
            $this->fail('Local or domain part was empty');
        }

        /** @var non-empty-string $local */
        /** @var non-empty-string $domain */
        return [$local, $domain];
    }

    public function testIdnNormalizationPreservesLocalCase(): void
    {
        $this->requireIdn();

        $input = 'UsEr+tag@Straße.DE'; // mixed case + IDN domain
        [$local, $rawDomain] = $this->splitMailbox($input);

        $expectedDomain = idn_to_ascii(strtolower($rawDomain), 0);
        if ($expectedDomain === false) {
            $this->markTestSkipped('idn_to_ascii failed for this domain on this platform');
        }

        $v = new EmailValidator();
        $res = $v->validate($input);

        // Format should be valid; local part kept as-is; domain normalized to IDNA ASCII + lowercase
        $this->assertTrue($res->isValid());
        $this->assertSame($local . '@' . $expectedDomain, $res->getNormalized());
        $this->assertSame($input, $res->getQuery());
        $this->assertNull($res->getSuggestion());
    }

    public function testIdnNormalizationWithSubdomain(): void
    {
        $this->requireIdn();

        $input = 'Local.Part@Sub.ÄÖÜ-Beispiel.DE';
        [$local, $rawDomain] = $this->splitMailbox($input);

        $expectedDomain = idn_to_ascii(strtolower($rawDomain), 0);
        if ($expectedDomain === false) {
            $this->markTestSkipped('idn_to_ascii failed for this domain on this platform');
        }

        $v = new EmailValidator();
        $res = $v->validate($input);

        $this->assertTrue($res->isValid());
        $this->assertSame($local . '@' . $expectedDomain, $res->getNormalized());
        // domain part should be lowercase in normalized output
        $this->assertStringEndsWith('@' . strtolower($expectedDomain), '@' . $expectedDomain);
    }
}
