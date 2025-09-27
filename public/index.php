<?php

declare(strict_types=1);

use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Value\ValidationResult;

require __DIR__ . '/../vendor/autoload.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

$sendJson = static function (int $status, ValidationResult | array $data): void {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
};

if ($path === '/' || $path === '/health') {
    $sendJson(200, ['status' => 'ok']);
    exit;
}

if ($path === '/validate') {
    $email = null;

    if ($method === 'GET') {
        $email = $_GET['email'] ?? null;
    } elseif ($method === 'POST') {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input') ?: '';
        if (stripos($ct, 'application/json') !== false) {
            $payload = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($payload)) {
                $email = $payload['email'] ?? null;
            }
        } elseif (stripos($ct, 'application/x-www-form-urlencoded') !== false) {
            $email = $_POST['email'] ?? null;
        }
    } else {
        $sendJson(405, ['error' => 'Method not allowed']);
        exit;
    }

    if (!is_string($email) || $email === '') {
        $sendJson(400, ['error' => 'Missing or invalid "email"']);
        exit;
    }

    $validator = new EmailValidator();
    $res = $validator->validate($email);
    $sendJson(200, $res);
    exit;
}

$sendJson(404, ['error' => 'Not found']);
