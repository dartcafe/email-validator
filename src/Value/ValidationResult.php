<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Value;

/**
 * Mutable DTO for validation outcome. Fluent setters for convenience.
 *
 * @psalm-type Reason = string         // syntax/domain/DNS related
 * @psalm-type Warning = string        // non-fatal (e.g., deny_list:<name>)
 */
final class ValidationResult implements
    \JsonSerializable
    /**
     * @property ?string $query              original queried email address (trimmed)
     * @property bool $valid                 format-only validity
     * @property bool $sendable              deliverability prediction
     * @property list<Reason> $reasons       reasons for invalidity or non-deliverability
     * @property list<Warning> $warnings     non-fatal issues
     * @property ?string $normalized         normalized email address (lowercased, trimmed, IDN to ASCII)
     * @property ?string $suggestion         suggestion for corrected email address (if any)
     * @property ?bool $domainExists         True if the domain exists, false if not, null if not checked
     * @property ?bool $hasMx                True if the domain has MX records, false if not, null if not checked
     * @property list<ListOutcome> $lists    list evaluation outcomes
     */
{
    private ?string $query = null;
    private bool $valid = false;
    private bool $sendable = false;

    /** @var list<Reason> */
    private array $reasons = [];

    /** @var list<Warning> */
    private array $warnings = [];

    private ?string $normalized = null;
    private ?string $suggestion = null;
    private ?float $suggestionScore = null;
    private ?bool $domainExists = null;
    private ?bool $hasMx = null;

    /** @var list<ListOutcome> */
    private array $lists = [];

    /**
     * @param ?string $query The original email address queried (trimmed), or null if not applicable
     */
    public function __construct(?string $query = null)
    {
        $this->query = $query !== null ? trim($query) : null;
    }

    /** Fluent setters */

    /**
     * Set format validity (syntax/domain checks only)
     *
     * @param bool $valid True if the email address is syntactically valid
     * @return $this
     */
    public function setValid(bool $valid): self
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * Set deliverability prediction (DNS checks only)
     *
     * @param bool $sendable True if the email address is likely deliverable
     * @return $this
     */
    public function setSendable(bool $sendable): self
    {
        $this->sendable = $sendable;
        return $this;
    }

    /**
     * Add multiple reasons at once
     *
     * @param list<string> $reasons
     * @return $this
     */
    public function addReasons(array $reasons): self
    {
        foreach ($reasons as $r) {
            $this->addReason($r);
        }
        return $this;
    }

    /**
     * Add a single reason if not already present
     *
     * @param string $reason
     * @return $this
     */
    public function addReason(string $reason): self
    {
        if (!in_array($reason, $this->reasons, true)) {
            $this->reasons[] = $reason;
        }
        return $this;
    }

    /**
     * Add a single warning if not already present
     *
     * @param string $warning
     * @return $this
     */
    public function addWarning(string $warning): self
    {
        if (!in_array($warning, $this->warnings, true)) {
            $this->warnings[] = $warning;
        }
        return $this;
    }

    /**
     * Set normalized email address (lowercased, trimmed, IDN to ASCII)
     *
     * @param ?string $normalized The normalized email address, or null if not applicable
     * @return $this
     */
    public function setNormalized(?string $normalized): self
    {
        $this->normalized = $normalized;
        return $this;
    }

    /**
     * Set suggestion for corrected email address (if any)
     *
     * @param ?string $suggestion The suggested corrected email address, or null if none
     * @return $this
     */
    public function setSuggestion(?string $suggestion): self
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    public function setSuggestionScore(?float $score): self
    {
        $this->suggestionScore = $score;
        return $this;
    }

    /**
     * Set DNS check results
     *
     * @param ?bool $domainExists True if the domain exists, false if not, null if not checked
     * @param ?bool $hasMx True if the domain has MX records, false if not, null if not checked
     * @return $this
     */
    public function setDns(?bool $domainExists, ?bool $hasMx): self
    {
        $this->domainExists = $domainExists;
        $this->hasMx = $hasMx;
        return $this;
    }

    /**
     * Set list evaluation outcomes
     *
     * @param list<ListOutcome> $lists
     * @return $this
     */
    public function setLists(array $lists): self
    {
        $this->lists = $lists;
        return $this;
    }

    /** Getters */

    /**
     * Determine if the email address is syntactically valid
     *
     * @return bool True if the email address is syntactically valid
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Determine if the email address is likely deliverable (DNS checks)
     *
     * @return bool True if the email address is likely deliverable
     */
    public function isSendable(): bool
    {
        return $this->sendable;
    }

    /**
     * Get list of reasons for invalidity or non-deliverability
     *
     * @return list<string> List of reasons for invalidity or non-deliverability
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }


    /**
     * Get list of warnings (non-fatal issues)
     *
     * @return list<string>
     * */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Determine if there are any warnings
     *
     * @return bool True if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /** Get the normalized email address
     *
     * @return ?string The normalized email address, or null if not set
     */
    public function getNormalized(): ?string
    {
        return $this->normalized;
    }

    /** Get the suggested corrected email address
     *
     * @return ?string The suggested corrected email address, or null if none
     */
    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    public function getSuggestionScore(): ?float
    {
        return $this->suggestionScore;
    }

    /** Get whether the domain exists (DNS check)
     *
     * @return ?bool True if the domain exists, false if not, null if not checked
     */
    public function getDomainExists(): ?bool
    {
        return $this->domainExists;
    }

    /** Get whether the domain has MX records (DNS check)
     *
     * @return ?bool True if the domain has MX records, false if not, null if not checked
     */
    public function getHasMx(): ?bool
    {
        return $this->hasMx;
    }

    /**
     * Get list evaluation outcomes
     *
     * @return list<ListOutcome> List of list evaluation outcomes
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * Get the original queried email address (trimmed)
     *
     * @return ?string The original queried email address, or null if not set
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return array{
     *   query: ?string,
     *   corrections: array{
     *      normalized:?string,
     *      suggestion:?string,
     *      suggestionScore:?float
     *   },
     *   simpleResults: array{
     *      formatValid:bool,
     *      isSendable:bool,
     *      hasWarnings:bool
     *   },
     *   reasons: list<string>,
     *   warnings: list<string>,
     *   dns: array{
     *      domainExists:?bool,
     *      hasMx:?bool
     *   },
     *   lists: list<ListOutcome>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'query'       => $this->getQuery(),
            'corrections' => [
                'normalized'      => $this->getNormalized(),
                'suggestion'      => $this->getSuggestion(),
                'suggestionScore' => $this->getSuggestionScore(),
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
