<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Value;

final class ListOutcome implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,       // listName
        public readonly string $humanName,  // display
        public readonly string $typ,        // 'allow' | 'deny'
        public readonly string $checkType,  // 'domain' | 'address'
        public readonly bool $matched,      // did it match?
        public readonly ?string $matchedValue = null, // which value matched (domain/address)
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'name'         => $this->name,
            'humanName'    => $this->humanName,
            'typ'          => $this->typ,
            'checkType'    => $this->checkType,
            'matched'      => $this->matched,
            'matchedValue' => $this->matchedValue,
        ];
    }
}
