<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Value;

/**
 * Mutable DTO for validation outcome. Fluent setters for convenience.
 *
 * @psalm-type Reason = string         // syntax/domain/DNS related
 * @psalm-type Warning = string        // non-fatal (e.g., deny_list:<name>)
 */
final class ValidationResult implements \JsonSerializable
{
    private ?string $query = null;
    private bool $valid = false;          // format-only validity
    private bool $sendable = false;       // deliverability prediction

    /** @var list<Reason> */
    private array $reasons = [];

    /** @var list<Warning> */
    private array $warnings = [];

    private ?string $normalized = null;
    private ?string $suggestion = null;
    private ?bool $domainExists = null;
    private ?bool $hasMx = null;

    /** @var list<ListOutcome> */
    private array $lists = [];

    public function __construct(?string $query = null)
    {
        $this->query = $query !== null ? trim($query) : null;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;
        return $this;
    }

    public function setSendable(bool $sendable): self
    {
        $this->sendable = $sendable;
        return $this;
    }

    /**
     * @param list<string> $reasons
     */
    public function addReasons(array $reasons): self
    {
        foreach ($reasons as $r) {
            $this->addReason($r);
        }
        return $this;
    }

    public function addReason(string $reason): self
    {
        if (!in_array($reason, $this->reasons, true)) {
            $this->reasons[] = $reason;
        }
        return $this;
    }

    public function addWarning(string $warning): self
    {
        if (!in_array($warning, $this->warnings, true)) {
            $this->warnings[] = $warning;
        }
        return $this;
    }

    public function setNormalized(?string $normalized): self
    {
        $this->normalized = $normalized;
        return $this;
    }

    public function setSuggestion(?string $suggestion): self
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    public function setDns(?bool $domainExists, ?bool $hasMx): self
    {
        $this->domainExists = $domainExists;
        $this->hasMx = $hasMx;
        return $this;
    }

    /**
     * @param list<ListOutcome> $lists
     */
    public function setLists(array $lists): self
    {
        $this->lists = $lists;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
    public function isSendable(): bool
    {
        return $this->sendable;
    }
    /**
     * @return list<string> */ public function getReasons(): array
    {
        return $this->reasons;
    }


    /**
     * @return list<string> */ public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    public function getNormalized(): ?string
    {
        return $this->normalized;
    }

    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    public function getDomainExists(): ?bool
    {
        return $this->domainExists;
    }

    public function getHasMx(): ?bool
    {
        return $this->hasMx;
    }

    /**
     * @return list<ListOutcome>
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return array{
     *   query: ?string,
     *   corrections: array{normalized:?string,suggestion:?string},
     *   simpleResults: array{formatValid:bool,isSendable:bool,hasWarnings:bool},
     *   reasons: list<string>,
     *   warnings: list<string>,
     *   dns: array{domainExists:?bool,hasMx:?bool},
     *   lists: list<ListOutcome>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'query'       => $this->getQuery(),
            'corrections' => [
                'normalized' => $this->getNormalized(),
                'suggestion' => $this->getSuggestion(),
            ],
            'simpleResults' => [
                'formatValid' => $this->isValid(),
                'isSendable'  => $this->isSendable(),
                'hasWarnings' => $this->hasWarnings(),
            ],
            'reasons'  => $this->getReasons(),
            'warnings' => $this->getWarnings(),
            'dns'      => [
                'domainExists' => $this->getDomainExists(),
                'hasMx'        => $this->getHasMx(),
            ],
            'lists' => $this->getLists(),
        ];
    }
}
