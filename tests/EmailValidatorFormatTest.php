<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\EmailValidator;
use PHPUnit\Framework\TestCase;

final class EmailValidatorFormatTest extends TestCase
{
    public function testEmptyInput(): void
    {
        $v = new EmailValidator();
        $res = $v->validate('  ');
        $this->assertFalse($res->isValid());
        $this->assertFalse($res->isSendable());
        $this->assertContains('empty', $res->getReasons());
        $this->assertNull($res->getNormalized());
        $this->assertNull($res->getSuggestion());
        $this->assertSame('', $res->getQuery());
    }

    public function testMissingAt(): void
    {
        $v = new EmailValidator();
        $res = $v->validate('user.example.com');
        $this->assertFalse($res->isValid());
        $this->assertContains('missing_at', $res->getReasons());
        $this->assertNull($res->getNormalized());
    }

    public function testSuggestionFromProvider(): void
    {
        // Stub suggestion provider: yaho.com -> yahoo.com
        $provider = new class () implements DomainSuggestionProvider {
            public function suggestDomain(string $domain): ?string
            {
                return $domain === 'yaho.com' ? 'yahoo.com' : null;
            }
        };

        $v = new EmailValidator($provider);
        $res = $v->validate('user@yaho.com');

        // Format is fine; suggestion should be present
        $this->assertTrue($res->isValid());
        $this->assertSame('user@yahoo.com', $res->getSuggestion());
        $this->assertSame('user@yaho.com', $res->getQuery());
        $this->assertSame('user@yaho.com', $res->getNormalized()); // normalized domain stays ascii same
    }
}
