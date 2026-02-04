<?php
/**
 * 임시 도구: designated_manager_id 수동 업데이트
 * URL: /admin-update-designated?request_id=xxx&manager_id=yyy
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$requestId = $_GET['request_id'] ?? '';
$managerId = $_GET['manager_id'] ?? '';

$pdo = require __DIR__ . '/database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST 데이터 확인
    $postRequestId = $_POST['request_id'] ?? '';
    $postManagerId = $_POST['manager_id'] ?? '';
    
    error_log('POST 데이터: request_id=' . $postRequestId . ', manager_id=' . $postManagerId);
    
    // 업데이트 실행
    $stmt = $pdo->prepare("UPDATE service_requests SET designated_manager_id = ? WHERE id = ?");
    $result = $stmt->execute([$postManagerId ?: null, $postRequestId]);
    
    $affected = $stmt->rowCount();
    error_log('업데이트 결과: affected_rows=' . $affected);
    
    $message = $result && $affected > 0 
        ? "✅ 업데이트 완료! (manager_id: " . ($postManagerId ?: 'NULL') . ")" 
        : "❌ 업데이트 실패 (affected_rows: $affected)";
    
    header('Location: /admin-update-designated?request_id=' . urlencode($postRequestId) . '&message=' . urlencode($message));
    exit;
}

// 현재 요청 정보 조회
$request = null;
if ($requestId) {
    $stmt = $pdo->prepare("SELECT sr.*, COALESCE(u.name, sr.guest_name, '비회원') as customer_name FROM service_requests sr LEFT JOIN users u ON u.id = sr.customer_id WHERE sr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 전체 매니저 목록
$managers = $pdo->query("SELECT id, name, phone FROM managers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 최근 요청 10개
$recent = $pdo->query("SELECT id, COALESCE((SELECT name FROM users WHERE id = customer_id), guest_name, '비회원') as customer_name, service_type, service_date, designated_manager_id, status FROM service_requests ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>지정 도우미 수동 업데이트</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">⚙️ 지정 도우미 수동 업데이트</h1>
        
        <?php if (isset($_GET['message'])): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
        <?php endif; ?>
        
        <!-- 최근 요청 목록 -->
        <div class="bg-white rounded-lg border p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">최근 요청 10개</h2>
            <div class="space-y-2">
                <?php foreach ($recent as $r): ?>
                <div class="flex items-center justify-between p-3 border rounded hover:bg-gray-50">
                    <div class="flex-1">
                        <span class="font-mono text-sm"><?= substr($r['id'], 0, 8) ?>...</span>
                        <span class="ml-3"><?= htmlspecialchars($r['customer_name']) ?></span>
                        <span class="ml-3 text-gray-600"><?= htmlspecialchars($r['service_type']) ?></span>
                        <span class="ml-3 text-sm text-gray-500"><?= htmlspecialchars($r['service_date']) ?></span>
                        <?php if ($r['designated_manager_id']): ?>
                        <span class="ml-3 text-green-600">✅ 지정됨</span>
                        <?php else: ?>
                        <span class="ml-3 text-red-600">❌ NULL</span>
                        <?php endif; ?>
                    </div>
                    <a href="?request_id=<?= urlencode($r['id']) ?>" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                        수정
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($request): ?>
        <!-- 업데이트 폼 -->
        <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">요청 정보</h2>
            <div class="space-y-2 mb-6">
                <p><strong>요청 ID:</strong> <?= htmlspecialchars($request['id']) ?></p>
                <p><strong>고객:</strong> <?= htmlspecialchars($request['customer_name']) ?></p>
                <p><strong>서비스:</strong> <?= htmlspecialchars($request['service_type']) ?></p>
                <p><strong>일시:</strong> <?= htmlspecialchars($request['service_date']) ?> <?= htmlspecialchars($request['start_time']) ?></p>
                <p><strong>현재 지정 도우미:</strong> 
                    <?php if ($request['designated_manager_id']): ?>
                    <span class="text-green-600"><?= htmlspecialchars($request['designated_manager_id']) ?></span>
                    <?php else: ?>
                    <span class="text-red-600">NULL</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">도우미 선택 (총 <?= count($managers) ?>명)</label>
                    <select name="manager_id" class="w-full border rounded-lg px-4 py-2" required>
                        <option value="">-- 도우미를 선택하세요 --</option>
                        <?php foreach ($managers as $m): ?>
                        <option value="<?= htmlspecialchars($m['id']) ?>" <?= $request['designated_manager_id'] === $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['phone']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">※ 지정 해제하려면 빈 값 선택</p>
                </div>
                <input type="hidden" name="request_id" value="<?= htmlspecialchars($requestId) ?>">
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    업데이트
                </button>
                
                <!-- 디버깅: 현재 값 표시 -->
                <div class="text-xs text-gray-500 mt-2">
                    <p>현재 요청 ID: <?= htmlspecialchars($requestId) ?></p>
                    <p>현재 지정 도우미 ID: <?= htmlspecialchars($request['designated_manager_id'] ?: 'NULL') ?></p>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
