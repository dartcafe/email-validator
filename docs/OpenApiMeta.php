<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '0.2.0',
        title: 'Email Validator API',
        description: 'Validates email format, predicts sendability, and warns on configured deny-lists.',
    ),
    servers: [
        new OA\Server(url: '/'),
    ],
    tags: [
        new OA\Tag(name: 'Utilities', description: 'Health checks and utilities'),
        new OA\Tag(name: 'Validation', description: 'Email validation endpoints'),
    ],
)]
final class ApiMeta
{
}
