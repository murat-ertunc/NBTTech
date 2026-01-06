<?php
// Lightweight token tests (no DB dependency).
// Run: php tests/token_test.php

$_ENV['APP_KEY'] = 'test-key';
putenv('APP_KEY=test-key');

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}

require __DIR__ . '/../app/Core/Token.php';

use App\Core\Token;

$tests = [
    'sign_and_verify' => function () {
        $payload = ['userId' => 42, 'role' => 'admin'];
        $token = Token::sign($payload, 60);
        $decoded = Token::verify($token);
        return $decoded && $decoded['userId'] === 42 && $decoded['role'] === 'admin';
    },
    'reject_tamper' => function () {
        $payload = ['userId' => 7];
        $token = Token::sign($payload, 60);
        // Tamper base part
        [$base, $sig] = explode('.', $token);
        $tampered = rtrim($base, '=') . 'x.' . $sig;
        return Token::verify($tampered) === null;
    },
    'reject_expired' => function () {
        $payload = ['userId' => 1];
        $token = Token::sign($payload, -5); // expired immediately
        return Token::verify($token) === null;
    },
];

$passed = 0;
foreach ($tests as $name => $fn) {
    $ok = false;
    try {
        $ok = $fn();
    } catch (Throwable $e) {
        $ok = false;
    }
    if ($ok) {
        $passed++;
        echo "[OK] $name\n";
    } else {
        echo "[FAIL] $name\n";
    }
}

echo "Passed $passed/" . count($tests) . " tests\n";
exit($passed === count($tests) ? 0 : 1);
