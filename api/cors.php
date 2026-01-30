<?php
/**
 * API CORS 헤더 (매니저 앱 / Vercel)
 * 와일드카드 패턴 지원: *.vercel.app
 */
if (!defined('API_CORS_ORIGINS')) {
    return;
}
$origins = array_map('trim', explode(',', API_CORS_ORIGINS));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array('*', $origins, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origin !== '') {
    // 정확한 매치 확인
    if (in_array($origin, $origins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // 와일드카드 패턴 확인 (예: *.vercel.app)
        foreach ($origins as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // *.vercel.app -> /^https:\/\/.*\.vercel\.app$/
                $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';
                if (preg_match($regex, $origin)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                    break;
                }
            }
        }
    }
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
