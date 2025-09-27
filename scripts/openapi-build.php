<?php
declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

$in  = realpath(__DIR__ . '/../public/openapi.yaml');
$out = __DIR__ . '/../public/openapi.json';

if ($in === false || !is_file($in)) {
	fwrite(STDERR, "[openapi:build] Spec not found at public/openapi.yaml\n");
	exit(1);
}

try {
	$data = Yaml::parseFile($in, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
	if (!is_array($data) || empty($data['openapi'])) {
		fwrite(STDERR, "[openapi:build] Invalid spec: missing 'openapi' key\n");
		exit(1);
	}

	$json = json_encode(
		$data,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	if ($json === false) {
		fwrite(STDERR, "[openapi:build] json_encode failed\n");
		exit(1);
	}

	if (@file_put_contents($out, $json) === false) {
		fwrite(STDERR, "[openapi:build] Cannot write to $out\n");
		exit(1);
	}

	$size = number_format((float)strlen($json) / 1024, 1);
	echo "[openapi:build] Wrote public/openapi.json ({$size} KiB)\n";
	exit(0);
} catch (\Throwable $e) {
	fwrite(STDERR, "[openapi:build] " . $e->getMessage() . "\n");
	exit(1);
}
