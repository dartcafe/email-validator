<?php
declare(strict_types=1);

namespace Dartcafe\EmailValidator\Docs;

use OpenApi\Attributes as OA;

final class Paths
{
    #[OA\Get(
        path: '/health',
        operationId: 'health',
        tags: ['meta'],
        summary: 'Health probe',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is alive',
                content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse')
            ),
        ]
    )]
    public function health(): void {}

    #[OA\Get(
        path: '/validate',
        operationId: 'validateGet',
        tags: ['validation'],
        summary: 'Validate an email (query param)',
        parameters: [
            new OA\QueryParameter(
                name: 'email',
                required: true,
                description: 'Email address to validate',
                schema: new OA\Schema(type: 'string', format: 'email')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Validation result',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationResult')
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function validateGet(): void {}

    #[OA\Post(
        path: '/validate',
        operationId: 'validatePost',
        tags: ['validation'],
        summary: 'Validate an email (JSON body)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EmailValidationRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Validation result',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationResult')
            ),
            new OA\Response(
                response: 415,
                description: 'Unsupported Media Type',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function validatePost(): void {}
}
