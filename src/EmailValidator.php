<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator;

use Dartcafe\EmailValidator\Contracts\DnsResolver;
use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\Contracts\ListProvider;
use Dartcafe\EmailValidator\Contracts\Validator;
use Dartcafe\EmailValidator\Dns\DefaultDnsResolver;
use Dartcafe\EmailValidator\Suggestion\TextDomainSuggestionProvider;
use Dartcafe\EmailValidator\Value\ValidationResult;

final class EmailValidator implements Validator
{
    private DomainSuggestionProvider $suggestions;
    private ?ListProvider $lists;
    private DnsResolver $dns;

    public function __construct(
        ?DomainSuggestionProvider $suggestions = null,
        ?ListProvider $lists = null,
        ?DnsResolver $dns = null,
    ) {
        $this->suggestions = $suggestions ?? TextDomainSuggestionProvider::default();
        $this->lists       = $lists;
        $this->dns         = $dns ?? new DefaultDnsResolver();
    }

    public function validate(string $emailAddress): ValidationResult
    {
        $emailAddress = trim($emailAddress);
        $result = (new ValidationResult($emailAddress)); // start with query

        if ($emailAddress === '') {
            return $result->setValid(false)->setSendable(false)->addReason('empty');
        }

        // Split early; if '@' is missing we can return immediately
        $atPos = strrpos($emailAddress, '@');
        if ($atPos === false) {
            return $result->setValid(false)->setSendable(false)->addReason('missing_at');
        }

        $local  = substr($emailAddress, 0, $atPos);
        $domain = substr($emailAddress, $atPos + 1);

        // Format-level checks
        $formatReasons = [];
        if (strlen($local) > 64) {
            $formatReasons[] = 'local_too_long';
        }
        if (strlen($domain) > 255) {
            $formatReasons[] = 'domain_too_long';
        }
        if (strlen($emailAddress) > 254) {
            $formatReasons[] = 'address_too_long';
        }

        // Normalize domain (lowercase + IDNA ASCII)
        $normalizedDomain = $this->toAsciiDomain(strtolower($domain));
        $normalized = $local . '@' . $normalizedDomain;
        $result->setNormalized($normalized);

        // Basic domain shape checks
        if (
            $normalizedDomain === '' ||
            str_contains($normalizedDomain, '..') ||
            str_starts_with($normalizedDomain, '-') ||
            str_ends_with($normalizedDomain, '-') ||
            str_starts_with($normalizedDomain, '.') ||
            str_ends_with($normalizedDomain, '.')
        ) {
            $formatReasons[] = 'domain_malformed';
        }

        // Syntax on normalized address (better for IDN)
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            $formatReasons[] = 'syntax';
        }

        // Domain typo suggestion (even if format issues exist)
        if ($normalizedDomain !== '') {
            $replacement = $this->suggestions->suggestDomain($normalizedDomain);
            if ($replacement !== null && $replacement !== $normalizedDomain) {
                $result->setSuggestion($local . '@' . $replacement);
            }
        }

        if (!empty($formatReasons)) {
            return $result
                ->setValid(false)
                ->setSendable(false)
                ->addReasons($formatReasons);
        }

        // Deliverability (DNS)
        [$domainExists, $hasMx] = $this->dns->check($normalizedDomain);
        $result->setDns($domainExists, $hasMx);

        if ($domainExists === false) {
            $result->addReason('domain_not_found');
        } elseif ($hasMx === false) {
            $result->addReason('no_mx');
        }

        if ($this->lists !== null) {
            $lists = $this->lists->evaluate(strtolower($normalized), $normalizedDomain);
            $result->setLists($lists);
            foreach ($lists as $o) {
                if ($o->type === 'deny' && $o->matched) {
                    $result->addWarning('deny_list:' . $o->name);
                }
            }
        }

        // Lists -> warnings (do not affect sendability)
        if ($this->lists !== null) {
            $lists = $this->lists->evaluate(strtolower($normalized), $normalizedDomain);
            $result->setLists($lists);
            foreach ($lists as $o) {
                if ($o->type === 'deny' && $o->matched) {
                    $result->addWarning('deny_list:' . $o->name);
                }
            }
        }

        // Final flags:
        // valid     -> format only (true here)
        // sendable  -> domain resolves AND MX present
        $sendable = ($domainExists === true) && ($hasMx === true);

        return $result->setValid(true)->setSendable($sendable);
    }

    private function toAsciiDomain(string $domain): string
    {
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($domain, IDNA_DEFAULT);
            return $ascii !== false ? $ascii : $domain;
        }
        return $domain;
    }
}
