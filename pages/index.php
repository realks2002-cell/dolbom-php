<?php
/**
 * 홈 (PRD 4.3)
 * - landing.html 디자인 기반 랜딩 페이지
 * - Hero, 서비스 카테고리, 이용 방법, 리뷰, CTA
 * - 공통 header/footer 사용
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
$base = rtrim(BASE_URL, '/');
$ctaHref = $base . '/requests/new';
$pageTitle = APP_NAME . ' - 믿을 수 있는 병원동행과 돌봄 서비스';
$mainClass = 'min-h-screen pt-0';

ob_start();
?>
    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 md:pt-48 md:pb-32 overflow-hidden">
        <!-- Background Blobs -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-orange-200/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-teal-200/30 rounded-full blur-3xl translate-y-1/4 -translate-x-1/4 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center relative z-10">
            <div class="space-y-8" data-aos="fade-up" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm text-sm font-medium text-orange-600 border border-orange-100">
                    <i data-lucide="star" class="w-4 h-4 fill-orange-500"></i>
                    <span>고객 만족도 99.8% 달성</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.15] text-gray-900">
                    당신의 일상에 <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-amber-500">따뜻한 동행</span>을 선물<br/>
                    합니다.
                </h1>
                <p class="text-lg md:text-xl text-gray-800 leading-relaxed max-w-lg">
                    병원 동행부터 가사, 육아, 일상 케어까지.<br class="hidden md:block"/>
                    전문 교육을 이수한 매니저가 가족의 마음으로 함께합니다.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="<?= $ctaHref ?>" class="text-lg px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white shadow-lg shadow-orange-500/20 rounded-full font-semibold transition-all duration-300 min-h-[44px] flex items-center justify-center">
                        서비스 신청하기
                    </a>
                    <a href="<?= $base ?>/bookings/guest-check" class="text-lg px-8 py-4 bg-[#ffc000] hover:bg-[#e6ad00] text-gray-900 rounded-full font-semibold transition-all duration-300 flex items-center justify-center gap-2 min-h-[44px] shadow-lg shadow-[#ffc000]/30" aria-label="내 서비스 확인하기">
                        <i data-lucide="clipboard-check" class="w-5 h-5 flex-shrink-0"></i>
                        내 서비스 확인하기
                    </a>
                </div>
                
                <div class="pt-8 flex items-center gap-6 text-base text-gray-700 font-medium">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-teal-600"></i>
                        신원 검증 완료
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-teal-600"></i>
                        전문 교육 이수
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-5 h-5 text-teal-600"></i>
                        24시간 예약 가능
                    </div>
                </div>
            </div>

            <div class="relative" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <div class="w-[85%] mx-auto bg-white p-4 pb-12 shadow-[0_4px_20px_rgba(0,0,0,0.15),0_8px_30px_rgba(0,0,0,0.1)] rotate-[-2deg] hover:rotate-0 transition-transform duration-300">
                    <img 
                        src="<?= $base ?>/assets/images/hero.jpg" 
                        alt="행복안심동행 서비스" 
                        class="w-full h-[320px] object-cover"
                    />
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20 px-4 md:px-8 max-w-7xl mx-auto bg-white rounded-[3rem] shadow-sm my-10">
        <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
            <span class="text-orange-600 font-semibold tracking-wide uppercase text-base">Our Services</span>
            <h2 class="text-4xl md:text-5xl font-bold mt-3 mb-6">어떤 도움이 필요하신가요?</h2>
            <p class="text-gray-800 text-xl">
                고객님의 상황에 딱 맞는 1:1 맞춤형 동행 서비스를 제공합니다.<br/>
                전문 매니저가 세심하게 케어해드립니다.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Service 1 -->
            <div class="group" data-aos="fade-up" data-aos-delay="0">
                <div class="h-full bg-stone-50 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-orange-100 hover:-translate-y-1">
                    <div class="w-full h-56 overflow-hidden relative">
                        <img src="<?= $base ?>/assets/images/seniorcare.jpg" alt="병원동행" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"/>
                    </div>
                    <div class="p-6">
                        <h3 class="text-3xl font-bold mb-3">병원동행</h3>
                        <p class="text-base text-gray-800 leading-relaxed mb-4">진료 접수부터 약국 처방까지, 병원 방문의 모든 과정을 가족처럼 든든하게 동행해드립니다.</p>
                        <a href="<?= $base ?>/service-guide" class="inline-flex items-center text-base font-semibold text-gray-900 hover:text-orange-600 transition-colors">자세히 보기 &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Service 2 -->
            <div class="group" data-aos="fade-up" data-aos-delay="100">
                <div class="h-full bg-stone-50 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-orange-100 hover:-translate-y-1">
                    <div class="w-full h-56 overflow-hidden relative">
                        <img src="<?= $base ?>/assets/images/babycare.jpg" alt="아이돌봄" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"/>
                    </div>
                    <div class="p-6">
                        <h3 class="text-3xl font-bold mb-3">아이돌봄</h3>
                        <p class="text-base text-gray-800 leading-relaxed mb-4">등하원 도우미부터 긴급 돌봄까지, 사랑과 정성으로 우리 아이의 행복한 시간을 책임집니다.</p>
                        <a href="<?= $base ?>/service-guide" class="inline-flex items-center text-base font-semibold text-gray-900 hover:text-orange-600 transition-colors">자세히 보기 &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Service 3 -->
            <div class="group" data-aos="fade-up" data-aos-delay="200">
                <div class="h-full bg-stone-50 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-orange-100 hover:-translate-y-1">
                    <div class="w-full h-56 overflow-hidden relative">
                        <img src="<?= $base ?>/assets/images/clean.jpg" alt="가사동행" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"/>
                    </div>
                    <div class="p-6">
                        <h3 class="text-3xl font-bold mb-3">가사동행</h3>
                        <p class="text-base text-gray-800 leading-relaxed mb-4">청소, 정리정돈, 반찬 만들기 등 쾌적한 주거 환경을 위해 세심한 가사 서비스를 제공합니다.</p>
                        <a href="<?= $base ?>/service-guide" class="inline-flex items-center text-base font-semibold text-gray-900 hover:text-orange-600 transition-colors">자세히 보기 &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Service 4 -->
            <div class="group" data-aos="fade-up" data-aos-delay="0">
                <div class="h-full bg-stone-50 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-orange-100 hover:-translate-y-1">
                    <div class="w-full h-56 overflow-hidden relative">
                        <img src="<?= $base ?>/assets/images/cook.jpg" alt="생활동행" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"/>
                    </div>
                    <div class="p-6">
                        <h3 class="text-3xl font-bold mb-3">생활동행</h3>
                        <p class="text-base text-gray-800 leading-relaxed mb-4">관공서 방문, 은행 업무, 장보기 등 혼자하기 힘든 일상 생활의 불편함을 해소해드립니다.</p>
                        <a href="<?= $base ?>/service-guide" class="inline-flex items-center text-base font-semibold text-gray-900 hover:text-orange-600 transition-colors">자세히 보기 &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Service 5 -->
            <div class="group" data-aos="fade-up" data-aos-delay="100">
                <div class="h-full bg-stone-50 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-orange-100 hover:-translate-y-1">
                    <div class="w-full h-56 overflow-hidden relative">
                        <img src="<?= $base ?>/assets/images/hero.jpg" alt="일상동행" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"/>
                    </div>
                    <div class="p-6">
                        <h3 class="text-3xl font-bold mb-3">일상동행</h3>
                        <p class="text-base text-gray-800 leading-relaxed mb-4">산책, 말벗, 취미 활동 공유 등 외로움을 덜어드리고 활기찬 하루를 선물합니다.</p>
                        <a href="<?= $base ?>/service-guide" class="inline-flex items-center text-base font-semibold text-gray-900 hover:text-orange-600 transition-colors">자세히 보기 &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Custom Request -->
            <div class="group" data-aos="fade-up" data-aos-delay="200">
                <div class="h-full bg-orange-500 text-white rounded-2xl p-8 flex flex-col justify-center items-center text-center hover:bg-orange-600 transition-colors shadow-lg shadow-orange-500/20">
                    <h3 class="text-3xl font-bold mb-4">찾으시는 서비스가<br/>없으신가요?</h3>
                    <p class="text-base text-orange-100 mb-8">
                        고객님의 상황에 맞는<br/>맞춤형 서비스를 상담해드립니다.
                    </p>
                    <a href="<?= $base ?>/faq" class="text-base bg-white text-orange-600 hover:bg-orange-50 w-full shadow-none border-0 px-6 py-3 rounded-full font-semibold transition-all min-h-[44px] flex items-center justify-center">
                        1:1 맞춤 상담하기
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Us Section -->
    <section id="why-us" class="py-24 px-4 md:px-8 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- 위: 제목 (1칸, 전체 너비) -->
            <div class="lg:col-span-2" data-aos="fade-up">
                <span class="text-teal-600 font-semibold tracking-wide uppercase text-base">Why Choose Us</span>
                <h2 class="text-4xl md:text-5xl font-bold mt-4 leading-tight">
                    믿을 수 있는 <span class="text-orange-500">행복안심동행</span>의 3가지 약속
                </h2>
            </div>
            
            <!-- 아래 왼쪽: 3가지 약속 내용 -->
            <div class="space-y-8" data-aos="fade-right">
                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-[50.4px] h-[50.4px] rounded-full bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-[1.6875rem]">1</div>
                    <p class="text-[1.35rem] text-gray-800 leading-relaxed"><span class="font-bold text-[1.6875rem] text-gray-900">엄격한 신원 검증</span> 모든 매니저는 신원 조회, 건강 검진, 인성 면접 등 5단계 검증 시스템을 통과했습니다.</p>
                </div>
                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-[50.4px] h-[50.4px] rounded-full bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-[1.6875rem]">2</div>
                    <p class="text-[1.35rem] text-gray-800 leading-relaxed"><span class="font-bold text-[1.6875rem] text-gray-900">전문 교육 이수</span> 병원 동행, 노인 케어, 아동 심리 등 분야별 100시간 이상의 전문 교육을 의무화합니다.</p>
                </div>
                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-[50.4px] h-[50.4px] rounded-full bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-[1.6875rem]">3</div>
                    <p class="text-[1.35rem] text-gray-800 leading-relaxed"><span class="font-bold text-[1.6875rem] text-gray-900">배상 책임 보험 가입</span> 만약의 상황에 대비하여 업계 최고 수준의 배상 책임 보험에 가입되어 있어 안심할 수 있습니다.</p>
                </div>
            </div>
            
            <!-- 아래 오른쪽: 이미지 (폴라로이드 효과) -->
            <div class="relative self-start" data-aos="fade-left">
                <div class="w-[70%] mx-auto bg-white p-4 pb-12 shadow-[0_4px_20px_rgba(0,0,0,0.15),0_8px_30px_rgba(0,0,0,0.1)] rotate-[-2deg] hover:rotate-0 transition-transform duration-300">
                    <img src="https://images.unsplash.com/photo-1758273238564-806f750a2cce?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" class="object-cover w-full h-[333px]" alt="서비스" />
                </div>
                <div class="absolute inset-0 bg-orange-500/5 -z-10 blur-3xl rounded-full"></div>
            </div>
        </div>
    </section>

    <!-- Review Section -->
    <section id="reviews" class="py-20 px-4 md:px-8 max-w-7xl mx-auto bg-orange-50/50 rounded-[3rem] my-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">고객님의 행복한 이야기</h2>
            <p class="text-lg text-gray-800">서비스를 이용하신 고객님들의 생생한 후기를 만나보세요.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div data-aos="fade-up" data-aos-delay="0">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-orange-100 h-full flex flex-col">
                    <div class="flex gap-1 mb-4">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                    </div>
                    <p class="text-lg text-gray-900 leading-relaxed mb-6 flex-grow">"바쁜 업무 때문에 어머니 병원 가시는 길을 챙겨드리지 못해 늘 죄송했는데, 매니저님이 친딸처럼 챙겨주셔서 너무 안심이 됩니다. 진료 내용도 꼼꼼히 정리해서 보내주셔서 감동했어요."</p>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-base font-bold text-gray-900">이OO 고객님 (직장인)</span>
                        <span class="text-sm bg-orange-100 text-orange-700 px-2 py-1 rounded-full">병원동행</span>
                    </div>
                </div>
            </div>
            <div data-aos="fade-up" data-aos-delay="100">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-orange-100 h-full flex flex-col">
                    <div class="flex gap-1 mb-4">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                    </div>
                    <p class="text-lg text-gray-900 leading-relaxed mb-6 flex-grow">"갑자기 아이가 아파서 급하게 돌봄 서비스가 필요했는데, 2시간 만에 오셔서 정말 구세주 같았어요. 아이가 선생님을 너무 좋아해서 정기 이용하기로 했습니다."</p>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-base font-bold text-gray-900">김OO 고객님 (워킹맘)</span>
                        <span class="text-sm bg-orange-100 text-orange-700 px-2 py-1 rounded-full">아이돌봄</span>
                    </div>
                </div>
            </div>
            <div data-aos="fade-up" data-aos-delay="200">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-orange-100 h-full flex flex-col">
                    <div class="flex gap-1 mb-4">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                    </div>
                    <p class="text-lg text-gray-900 leading-relaxed mb-6 flex-grow">"혼자 사시는 아버지 반찬이 늘 걱정이었는데, 가사동행 서비스 덕분에 냉장고가 꽉 찼다고 좋아하시네요. 집안 분위기도 훨씬 밝아진 것 같아 감사합니다."</p>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-base font-bold text-gray-900">박OO 고객님</span>
                        <span class="text-sm bg-orange-100 text-orange-700 px-2 py-1 rounded-full">가사동행</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-6">
        <div class="max-w-5xl mx-auto bg-gradient-to-br from-teal-800 to-teal-900 rounded-[3rem] p-10 md:p-20 text-center text-white relative overflow-hidden shadow-2xl" data-aos="zoom-in">
            <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-teal-500 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-500 rounded-full blur-3xl opacity-30"></div>

            <div class="relative z-10 space-y-8">
                <h2 class="text-3xl md:text-5xl font-bold">
                    사랑하는 가족을 위한<br/>
                    <span class="text-teal-200">따뜻한 동행</span>, 지금 시작하세요.
                </h2>
                <p class="text-teal-100 text-lg md:text-xl max-w-2xl mx-auto">
                    상담은 언제나 무료입니다. 고객님의 상황에 맞는 최적의 서비스를 제안해드립니다.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4 pt-4">
                    <a href="<?= KAKAO_CHAT_URL ?>" target="_blank" rel="noopener noreferrer" class="bg-[#ffc000] hover:bg-[#e6ad00] text-gray-900 text-lg px-10 py-3 rounded-full font-bold shadow-lg transition-all min-h-[44px] flex items-center justify-center gap-2" aria-label="카카오톡 상담">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c5.799 0 10.5 3.664 10.5 8.185 0 4.52-4.701 8.184-10.5 8.184a13.5 13.5 0 0 1-1.727-.11l-4.408 2.883c-.501.265-.678.236-.472-.413l.892-3.678c-2.88-1.46-4.785-3.99-4.785-6.866C1.5 6.665 6.201 3 12 3z"/></svg>
                        카톡 상담하기
                    </a>
                    <a href="<?= $base ?>/service-guide" class="border border-teal-400 text-teal-100 hover:bg-teal-800 hover:text-white text-lg px-10 py-3 rounded-full font-bold transition-all min-h-[44px] flex items-center justify-center">
                        서비스 요금 보기
                    </a>
                </div>
                <p class="text-xl text-teal-400 pt-6">
                    * 평일 09:00 - 18:00 (점심시간 12:00 - 13:00)
                </p>
            </div>
        </div>
    </section>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__) . '/components/layout.php';
?>
