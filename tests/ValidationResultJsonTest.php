<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\Value\ListOutcome;
use Dartcafe\EmailValidator\Value\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultJsonTest extends TestCase
{
    public function testJsonStructure(): void
    {
        // Build a result manually
        $res = (new ValidationResult('user@yaho.com'))
            ->setNormalized('user@yaho.com')
            ->setSuggestion('user@yahoo.com')
            ->setValid(true)
            ->setSendable(true)
            ->addReason('no_mx')         // example reason
            ->addWarning('deny_list:disposable')
            ->setDns(true, false)
            ->setLists([
                new ListOutcome('disposable', 'Disposable providers', 'deny', 'domain', true, 'mailinator.com'),
            ]);

        $json = json_encode($res, JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(2, strlen($json));

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        /** @var array{
         *   query: ?string,
         *   corrections: array{normalized:?string,suggestion:?string},
         *   simpleResults: array{formatValid:bool,isSendable:bool,hasWarnings:bool},
         *   reasons: list<string>,
         *   warnings: list<string>,
         *   dns: array{domainExists:?bool,hasMx:?bool},
         *   lists: list<array{name:string,humanName:string,typ:string,checkType:string,matched:bool,matchedValue:?string}>
         * } $data
         */

        $this->assertSame('user@yaho.com', $data['query']);
        $this->assertSame('user@yaho.com', $data['corrections']['normalized']);
        $this->assertSame('user@yahoo.com', $data['corrections']['suggestion']);

        $this->assertArrayHasKey('simpleResults', $data);
        $this->assertTrue($data['simpleResults']['formatValid']);
        $this->assertTrue($data['simpleResults']['isSendable']);
        $this->assertTrue($data['simpleResults']['hasWarnings']);

        $this->assertContains('no_mx', $data['reasons']);
        $this->assertContains('deny_list:disposable', $data['warnings']);

        $this->assertArrayHasKey('dns', $data);
        $this->assertTrue($data['dns']['domainExists']);
        $this->assertFalse($data['dns']['hasMx']);

        $this->assertNotEmpty($data['lists']);
        $this->assertNotEmpty($data['lists']);
        $this->assertSame('disposable', $data['lists'][0]['name']);
    }

}
