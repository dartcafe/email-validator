<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Contracts;

interface DnsResolver
{
    /**
     * DNS deliverability checker. Returns [domainExists, hasMx].
     * Use nulls when DNS is unavailable or disabled.
     *
     * @return array{0:?bool,1:?bool} [domainExists, hasMx]
     */
    public function check(string $asciiLowerDomain): array;
}
