<?php
/**
 * JWT 헬퍼 (HS256)
 * API 토큰 인증용 — Composer 불필요
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

function jwt_encode(array $payload, string $secret): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = base64url_encode(json_encode($header));
    $p = base64url_encode(json_encode($payload));
    $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);
    return $h . '.' . $p . '.' . base64url_encode($sig);
}

function jwt_decode(string $token, string $secret): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$h, $p, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', $h . '.' . $p, $secret, true));
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $payload = json_decode(base64url_decode($p), true);
    if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }
    return $payload;
}

function base64url_encode(string $s): string {
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

function base64url_decode(string $s): string {
    $s .= str_repeat('=', (4 - strlen($s) % 4) % 4);
    return base64_decode(strtr($s, '-_', '+/'));
}
