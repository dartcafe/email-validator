<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\EmailValidator;
use PHPUnit\Framework\TestCase;

final class EmailValidatorRfcCaseTest extends TestCase
{
    public function testNormalizedKeepsLocalCase(): void
    {
        $v = new EmailValidator();
        $res = $v->validate('UsEr.Name+Tag@ExAmPlE.COM');

        $this->assertTrue($res->isValid());
        $this->assertSame('UsEr.Name+Tag@example.com', $res->getNormalized(), 'Local part must keep original case; domain lowercased');
    }

    public function testSuggestionKeepsLocalCase(): void
    {
        // yaho.com -> yahoo.com
        $provider = new class () implements DomainSuggestionProvider {
            public function suggestDomain(string $domain): ?string
            {
                return $domain === 'yaho.com' ? 'yahoo.com' : null;
            }
        };

        $v = new EmailValidator($provider);
        $res = $v->validate('UsEr@yaho.com');

        $this->assertTrue($res->isValid());
        $this->assertSame('UsEr@yahoo.com', $res->getSuggestion(), 'Suggestion must preserve local case and only fix the domain');
    }
}
