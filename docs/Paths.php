<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Docs;

use OpenApi\Attributes as OA;

final class ValidatePath
{
    #[OA\Get(
        path: '/validate',
        tags: ['Validation'],
        summary: 'Validate an email address (query parameter)',
        parameters: [
            new OA\QueryParameter(
                name: 'email',
                required: true,
                description: 'Email address to validate',
                schema: new OA\Schema(type: 'string', format: 'email', example: 'user@mailinator.com'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Validation result',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/EmailValidationResponse',
                    examples: [
                        new OA\Examples(
                            example: 'deny_disposable',
                            summary: 'Format ok; deliverable; deny list warning',
                            value: [
                                'query'       => 'user@mailinator.com',
                                'corrections' => [
                                    'normalized' => 'user@mailinator.com',
                                    'suggestion' => null,
                                ],
                                'simpleResults' => [
                                    'formatValid' => true,
                                    'isSendable'  => true,
                                    'hasWarnings' => true,
                                ],
                                'reasons'  => [],
                                'warnings' => ['deny_list:disposable'],
                                'dns'      => [
                                    'domainExists' => true,
                                    'hasMx'        => true,
                                ],
                                'lists' => [[
                                    'name'         => 'disposable',
                                    'humanName'    => 'Disposable providers',
                                    'typ'          => 'deny',
                                    'checkType'    => 'domain',
                                    'matched'      => true,
                                    'matchedValue' => 'mailinator.com',
                                ]],
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Missing or invalid query parameter',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function validateGet(): void
    {
    }

    #[OA\Post(
        path: '/validate',
        tags: ['Validation'],
        summary: 'Validate an email address (request body)',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/EmailValidationRequest'),
                    examples: [
                        new OA\Examples(
                            example: 'simple',
                            summary: 'Simple request',
                            value: ['email' => 'user@example.com'],
                        ),
                    ],
                ),
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(ref: '#/components/schemas/EmailValidationRequest'),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Validation result',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/EmailValidationResponse',
                    examples: [
                        new OA\Examples(
                            example: 'valid_sendable',
                            summary: 'Format ok and sendable',
                            value: [
                                'query'       => 'user@example.com',
                                'corrections' => [
                                    'normalized' => 'user@example.com',
                                    'suggestion' => null,
                                ],
                                'simpleResults' => [
                                    'formatValid' => true,
                                    'isSendable'  => true,
                                    'hasWarnings' => false,
                                ],
                                'reasons'  => [],
                                'warnings' => [],
                                'dns'      => [
                                    'domainExists' => true,
                                    'hasMx'        => true,
                                ],
                                'lists' => [],
                            ],
                        ),
                        new OA\Examples(
                            example: 'no_mx',
                            summary: 'Format ok but not sendable (no MX)',
                            value: [
                                'query'       => 'user@nomx.example',
                                'corrections' => [
                                    'normalized' => 'user@nomx.example',
                                    'suggestion' => null,
                                ],
                                'simpleResults' => [
                                    'formatValid' => true,
                                    'isSendable'  => false,
                                    'hasWarnings' => false,
                                ],
                                'reasons'  => ['no_mx'],
                                'warnings' => [],
                                'dns'      => [
                                    'domainExists' => true,
                                    'hasMx'        => false,
                                ],
                                'lists' => [],
                            ],
                        ),
                        new OA\Examples(
                            example: 'format_error',
                            summary: 'Invalid format (early return)',
                            value: [
                                'query'       => 'user.example.com',
                                'corrections' => [
                                    'normalized' => null,
                                    'suggestion' => null,
                                ],
                                'simpleResults' => [
                                    'formatValid' => false,
                                    'isSendable'  => false,
                                    'hasWarnings' => false,
                                ],
                                'reasons'  => ['missing_at'],
                                'warnings' => [],
                                'dns'      => [
                                    'domainExists' => null,
                                    'hasMx'        => null,
                                ],
                                'lists' => [],
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Missing or invalid input',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function validatePost(): void
    {
    }
}

#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', type: 'string', description: 'Human-readable error message'),
    ],
)]
final class ErrorResponse
{
}
