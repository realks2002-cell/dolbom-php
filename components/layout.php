<?php
/**
 * 공통 레이아웃
 * - head, Tailwind, Noto Sans KR, custom.css
 * - header, main, footer
 * 사용: $pageTitle, $hideHeader, $hideFooter, $mainClass 설정 후 include
 */
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once dirname(__DIR__) . '/includes/auth.php';
$pageTitle = $pageTitle ?? APP_NAME;
$hideHeader = $hideHeader ?? false;
$hideFooter = $hideFooter ?? false;
$mainClass = $mainClass ?? 'min-h-screen';
$base = rtrim(BASE_URL, '/');
$layoutContent = $layoutContent ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="믿을 수 있는 병원동행과 돌봄 서비스 - Hangbok77">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Noto Sans KR', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: { DEFAULT: '#2563eb' },
                    },
                },
            },
        };
    </script>
</head>
<body class="font-sans antialiased">
<?php if (!$hideHeader) { include __DIR__ . '/header.php'; } ?>
<main class="<?= htmlspecialchars($mainClass) ?>" role="main">
    <?= $layoutContent ?>
</main>
<?php if (!$hideFooter) { include __DIR__ . '/footer.php'; } ?>
<script src="<?= $base ?>/assets/js/main.js"></script>
</body>
</html>
