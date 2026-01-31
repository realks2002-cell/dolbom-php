<?php
/**
 * 로그아웃
 * URL: /auth/logout
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();
$_SESSION = [];
session_destroy();
redirect('/');
