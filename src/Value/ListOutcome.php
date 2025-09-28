<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Value;

/**
 * Result of evaluating one list (allow/deny, domain/address) against an email address.
 */
final class ListOutcome implements \JsonSerializable
{
    /**
     * @param string      $name         listName
     * @param string      $humanName    display
     * @param string      $type         'allow' | 'deny'
     * @param string      $checkType    'domain' | 'address'
     * @param bool        $matched      did it match?
     * @param ?string     $matchedValue which value matched (domain/address)
     */
    public function __construct(
        public readonly string $name,
        public readonly string $humanName,
        public readonly string $type,
        public readonly string $checkType,
        public readonly bool $matched,
        public readonly ?string $matchedValue = null,
    ) {
    }

    /**
     * @return array{
     *   name: string,
     *   humanName: string,
     *   type: string,
     *   checkType: string,
     *   matched: bool,
     *   matchedValue: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'name'         => $this->name,
            'humanName'    => $this->humanName,
            'type'         => $this->type,
            'checkType'    => $this->checkType,
            'matched'      => $this->matched,
            'matchedValue' => $this->matchedValue,
        ];
    }
}
