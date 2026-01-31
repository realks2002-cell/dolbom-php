<?php
/**
 * 공통 푸터 (랜딩 디자인)
 * - 회사 소개, 서비스 링크, 고객지원, 저작권
 */
$base = $base ?? rtrim(BASE_URL ?? '', '/');
?>
<footer id="footer" class="bg-gray-900 text-white py-12 border-t border-gray-800" role="contentinfo">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-10 mb-10">
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-2 mb-4 text-white">
                    <i data-lucide="heart-handshake" class="text-orange-500 w-6 h-6"></i>
                    <span class="text-2xl font-bold"><?= APP_NAME ?></span>
                </div>
                <p class="text-lg leading-relaxed max-w-sm text-white">
                    우리는 고객의 삶에 따뜻한 온기를 전하는 동반자입니다.<br/>
                    신뢰와 정성으로 가장 가까운 곳에서 함께하겠습니다.
                </p>
            </div>
            <div>
                <h4 class="text-lg text-white font-bold mb-4">서비스</h4>
                <ul class="space-y-2 text-lg">
                    <li><a href="<?= $base ?>/service-guide" class="text-white hover:text-orange-400 transition-colors">병원동행</a></li>
                    <li><a href="<?= $base ?>/service-guide" class="text-white hover:text-orange-400 transition-colors">아이돌봄</a></li>
                    <li><a href="<?= $base ?>/service-guide" class="text-white hover:text-orange-400 transition-colors">가사동행</a></li>
                    <li><a href="<?= $base ?>/service-guide" class="text-white hover:text-orange-400 transition-colors">일상/생활동행</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg text-white font-bold mb-4">고객지원</h4>
                <ul class="space-y-2 text-lg">
                    <li><a href="<?= $base ?>/faq" class="text-white hover:text-orange-400 transition-colors">자주 묻는 질문</a></li>
                    <li><a href="<?= $base ?>/manager/recruit" class="text-white hover:text-orange-400 transition-colors">매니저 지원</a></li>
                    <li><a href="<?= $base ?>/about" class="text-white hover:text-orange-400 transition-colors">회사소개</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-base">
            <p class="text-white">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            <div class="flex gap-4">
                <a href="#" class="text-white hover:text-orange-400 transition-colors">개인정보처리방침</a>
                <a href="#" class="text-white hover:text-orange-400 transition-colors">이용약관</a>
            </div>
        </div>
    </div>
</footer>
