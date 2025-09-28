<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Dns;

use Dartcafe\EmailValidator\Contracts\DnsResolver;

final class DefaultDnsResolver implements DnsResolver
{
    /**
     * Check DNS records for the given domain.
     *
     * @param string $asciiLowerDomain Domain in ASCII lowercase
     * @return array{0:?bool,1:?bool} [domainExists, hasMx]
     */
    public function check(string $asciiLowerDomain): array
    {
        $mx = @dns_get_record($asciiLowerDomain, DNS_MX);
        if (is_array($mx) && $mx !== []) {
            return [true, true];
        }
        $a = @dns_get_record($asciiLowerDomain, DNS_A);
        $aaaa = @dns_get_record($asciiLowerDomain, DNS_AAAA);
        $exists = ((is_array($a) && $a !== []) || (is_array($aaaa) && $aaaa !== [])) ? true : false;
        return [$exists, false];
    }
}
