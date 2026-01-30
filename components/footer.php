<?php
/**
 * 공통 푸터 (PRD 4.1)
 * - 회사 정보, 이용약관, 개인정보처리방침, 고객센터, SNS
 */
$base = $base ?? rtrim(BASE_URL ?? '', '/');
?>
<footer class="border-t bg-gray-50 py-12" role="contentinfo">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-lg font-semibold"><?= APP_NAME ?></p>
                <p class="mt-2 text-sm text-gray-600">믿을 수 있는 병원동행과 돌봄 서비스</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">이용 안내</p>
                <ul class="mt-2 space-y-1 text-sm text-gray-600">
                    <li><a href="<?= $base ?>/terms" class="hover:text-gray-900">이용약관</a></li>
                    <li><a href="<?= $base ?>/privacy" class="hover:text-gray-900">개인정보처리방침</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">고객센터</p>
                <p class="mt-2 text-sm text-gray-600">고객센터 링크 (추후)</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">SNS</p>
                <p class="mt-2 text-sm text-gray-600">SNS 링크 (추후)</p>
            </div>
        </div>
        <p class="mt-8 border-t pt-8 text-center text-sm text-gray-500">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
    </div>
</footer>
