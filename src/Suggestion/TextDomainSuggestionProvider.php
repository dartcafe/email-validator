<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;

final class TextDomainSuggestionProvider implements DomainSuggestionProvider
{
    /** Minimal sane defaults */
    private const DEFAULTS = [
        'gmail.com','yahoo.com','outlook.com','hotmail.com','live.com',
        'icloud.com','gmx.de','web.de','t-online.de','proton.me',
    ];

    private DomainSuggestionProvider $engine;

    private function __construct(DomainSuggestionProvider $engine)
    {
        $this->engine = $engine;
    }

    public static function default(): self
    {
        return new self(new ArrayDomainSuggestionProvider(self::DEFAULTS));
    }

    public function suggestDomain(string $domain): ?string
    {
        return $this->engine->suggestDomain($domain);
    }
}
