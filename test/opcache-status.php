<?php
/**
 * OPcache 상태 확인
 */
header('Content-Type: application/json; charset=utf-8');

$status = [
    'opcache_enabled' => function_exists('opcache_get_status'),
    'opcache_status' => null,
    'php_version' => PHP_VERSION,
];

if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status(false);
    if ($opcache) {
        $status['opcache_status'] = [
            'enabled' => $opcache['opcache_enabled'] ?? false,
            'cache_full' => $opcache['cache_full'] ?? false,
            'memory_usage' => [
                'used_memory_mb' => round(($opcache['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2),
                'free_memory_mb' => round(($opcache['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 2),
            ],
            'statistics' => [
                'cached_scripts' => $opcache['opcache_statistics']['num_cached_scripts'] ?? 0,
                'hits' => $opcache['opcache_statistics']['hits'] ?? 0,
                'misses' => $opcache['opcache_statistics']['misses'] ?? 0,
            ]
        ];
    }
}

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
