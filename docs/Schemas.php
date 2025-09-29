<?php
declare(strict_types=1);

namespace Dartcafe\EmailValidator\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "EmailValidationRequest",
    type: "object",
    required: ["email"],
    properties: [
        new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
    ],
    example: ['email' => 'user@example.com']
)]
final class EmailValidationRequest {}

#[OA\Schema(
    schema: "ListOutcome",
    type: "object",
    required: ["name", "humanName", "type", "checkType", "matched"],
    properties: [
        new OA\Property(property: "name",       type: "string",  example: "deny_disposable"),
        new OA\Property(property: "humanName",  type: "string",  example: "Disposable domains"),
        new OA\Property(property: "type",       type: "string",  enum: ["allow","deny"], example: "deny"),
        new OA\Property(property: "checkType",  type: "string",  enum: ["domain","address"], example: "domain"),
        new OA\Property(property: "matched",    type: "boolean", example: false),
        new OA\Property(property: "matchedValue", type: "string", nullable: true, example: null),
    ]
)]
final class ListOutcome {}

#[OA\Schema(
    schema: "ValidationResult",
    type: "object",
    properties: [
        new OA\Property(property: "query", type: "string", nullable: true, example: "User+tag@Straße.de"),
        new OA\Property(
            property: "corrections",
            type: "object",
            properties: [
                new OA\Property(property: "normalized", type: "string", nullable: true, example: "User+tag@strasse.de"),
                new OA\Property(property: "suggestion", type: "string", nullable: true, example: null),
            ]
        ),
        new OA\Property(
            property: "simpleResults",
            type: "object",
            properties: [
                new OA\Property(property: "formatValid", type: "boolean", example: true),
                new OA\Property(property: "isSendable",  type: "boolean", example: true),
                new OA\Property(property: "hasWarnings", type: "boolean", example: false),
            ]
        ),
        new OA\Property(property: "reasons",   type: "array", items: new OA\Items(type: "string"), example: []),
        new OA\Property(property: "warnings",  type: "array", items: new OA\Items(type: "string"), example: []),
        new OA\Property(
            property: "dns",
            type: "object",
            properties: [
                new OA\Property(property: "domainExists", type: "boolean", nullable: true, example: true),
                new OA\Property(property: "hasMx",        type: "boolean", nullable: true, example: true),
            ]
        ),
        new OA\Property(
            property: "lists",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/ListOutcome")
        ),
    ],
    example: [
        'query' => 'User+tag@Straße.de',
        'corrections' => ['normalized' => 'User+tag@strasse.de', 'suggestion' => null],
        'simpleResults' => ['formatValid' => true, 'isSendable' => true, 'hasWarnings' => false],
        'reasons' => [],
        'warnings' => [],
        'dns' => ['domainExists' => true, 'hasMx' => true],
        'lists' => [[
            'name' => 'deny_disposable',
            'humanName' => 'Disposable domains',
            'type' => 'deny',
            'checkType' => 'domain',
            'matched' => false,
            'matchedValue' => null,
        ]],
    ]
)]
final class ValidationResult {}

#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    required: ["error"],
    properties: [
        new OA\Property(property: "error", type: "string", example: "Use application/json"),
    ],
    example: ['error' => 'Bad request']
)]
final class ErrorResponse {}

#[OA\Schema(
    schema: "HealthResponse",
    type: "object",
    required: ["status"],
    properties: [
        new OA\Property(property: "status", type: "string", example: "ok"),
    ],
    example: ['status' => 'ok']
)]
final class HealthResponse {}
