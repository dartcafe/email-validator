<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmailValidationRequest',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email address to validate'),
    ],
)]
final class EmailValidationRequest
{
}

#[OA\Schema(
    schema: 'Reason',
    description: 'Format/DNS reason codes',
    type: 'string',
    enum: [
        'empty','syntax','missing_at','local_too_long','domain_too_long',
        'address_too_long','domain_malformed','domain_not_found','no_mx',
    ],
)]
final class Reason
{
}

#[OA\Schema(
    schema: 'Warning',
    description: 'Non-fatal warnings (e.g., deny list hits)',
    oneOf: [
        new OA\Schema(type: 'string', pattern: '^deny_list:[A-Za-z0-9_\\-]+$'),
    ],
)]
final class Warning
{
}

#[OA\Schema(
    schema: 'ListOutcome',
    type: 'object',
    description: 'Outcome for a single configured list.',
    required: ['name','humanName','type','checkType','matched'],
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Technical list identifier'),
        new OA\Property(property: 'humanName', type: 'string', description: 'Display name'),
        new OA\Property(property: 'type', type: 'string', enum: ['allow','deny']),
        new OA\Property(property: 'checkType', type: 'string', enum: ['domain','address']),
        new OA\Property(property: 'matched', type: 'boolean'),
        new OA\Property(property: 'matchedValue', type: 'string', nullable: true, description: 'Matched domain or address'),
    ],
)]
final class ListOutcome
{
}

#[OA\Schema(
    schema: 'Corrections',
    type: 'object',
    properties: [
        new OA\Property(property: 'normalized', type: 'string', nullable: true, description: 'Lowercased domain, IDNA ASCII'),
        new OA\Property(property: 'suggestion', type: 'string', nullable: true, description: 'Suggested fix for common domain typos'),
    ],
)]
final class Corrections
{
}

#[OA\Schema(
    schema: 'SimpleResults',
    type: 'object',
    required: ['formatValid','isSendable','hasWarnings'],
    properties: [
        new OA\Property(property: 'formatValid', type: 'boolean', description: 'Format validity only (syntax/structure)'),
        new OA\Property(property: 'isSendable', type: 'boolean', description: 'Deliverability prediction: true only if domain resolves and MX exists'),
        new OA\Property(property: 'hasWarnings', type: 'boolean', description: 'True if any non-fatal warnings present'),
    ],
)]
final class SimpleResults
{
}

#[OA\Schema(
    schema: 'DnsResult',
    type: 'object',
    properties: [
        new OA\Property(property: 'domainExists', type: 'boolean', nullable: true, description: 'Domain has A/AAAA records (null if not checked)'),
        new OA\Property(property: 'hasMx', type: 'boolean', nullable: true, description: 'Domain has MX records (null if not checked)'),
    ],
)]
final class DnsResult
{
}

#[OA\Schema(
    schema: 'EmailValidationResponse',
    type: 'object',
    description: 'Validation outcome with format, deliverability, warnings and list matches.',
    required: ['simpleResults','reasons','warnings','corrections','dns','lists'],
    properties: [
        new OA\Property(property: 'query', type: 'string', nullable: true, description: 'Input email as received (trimmed)'),
        new OA\Property(property: 'corrections', ref: '#/components/schemas/Corrections'),
        new OA\Property(property: 'simpleResults', ref: '#/components/schemas/SimpleResults'),
        new OA\Property(property: 'reasons', type: 'array', items: new OA\Items(ref: '#/components/schemas/Reason')),
        new OA\Property(property: 'warnings', type: 'array', items: new OA\Items(ref: '#/components/schemas/Warning')),
        new OA\Property(property: 'dns', ref: '#/components/schemas/DnsResult'),
        new OA\Property(
            property: 'lists',
            type: 'array',
            description: 'Per-list outcomes based on configured lists.ini',
            items: new OA\Items(ref: '#/components/schemas/ListOutcome'),
        ),
    ],
)]
final class EmailValidationResponse
{
}
