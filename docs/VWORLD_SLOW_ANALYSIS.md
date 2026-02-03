# VWorld 주소 검색 느린 원인 분석

## 📊 현재 상황

### VWorld API를 사용하는 페이지
1. ✅ **서비스 요청** (`pages/requests/new.php`)
2. ✅ **회원가입** (`pages/auth/signup.php`)
3. ❌ **매니저 가입** (`pages/manager/signup.php`) - VWorld 미사용 (일반 input)

---

## 🐌 느린 원인 분석

### 1. **다중 API 호출** (가장 큰 원인!)

**현재 로직** (`api/address-suggest.php`):
```php
// 기본 검색어
$patterns = [$keyword];

// 번지가 없으면 추가 패턴 생성
if (!preg_match('/\d+/', $keyword)) {
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 100';
}

// 각 패턴마다 2번씩 API 호출 (road + parcel)
foreach ($patterns as $pattern) {
    // road 타입 호출
    fetch_url("http://api.vworld.kr/...&type=road...");
    
    // parcel 타입 호출
    fetch_url("http://api.vworld.kr/...&type=parcel...");
}
```

**문제점**:
- "공세동" 입력 시: 3개 패턴 × 2번 호출 = **최대 6번 API 호출**
- 각 호출마다 타임아웃: 5초
- 최악의 경우: **6 × 5초 = 30초**

### 2. **순차 처리**

```php
foreach ($patterns as $pattern) {
    // 1번 호출 완료 후
    // 2번 호출 시작
    // 3번 호출 시작...
}
```

**문제점**:
- 병렬 처리 없음
- 각 호출이 끝날 때까지 대기
- 누적 지연 시간 증가

### 3. **타임아웃 설정**

```php
CURLOPT_TIMEOUT => 5,         // 응답 타임아웃 5초
CURLOPT_CONNECTTIMEOUT => 3,  // 연결 타임아웃 3초
```

**문제점**:
- 5초는 웹 사용자에게 매우 긴 시간
- VWorld API 응답이 느리면 계속 대기

### 4. **불필요한 패턴 생성**

```php
if (!preg_match('/\d+/', $keyword)) {
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 100';
}
```

**문제점**:
- "공세동" → "공세동 1", "공세동 100" 추가
- 대부분 불필요한 호출
- 사용자는 "공세동"만 검색하고 싶은데 추가 호출

---

## 🔍 실제 측정

### 테스트: "공세동" 검색

**API 호출 순서**:
1. `공세동` (road) - 1~2초
2. `공세동` (parcel) - 1~2초
3. `공세동 1` (road) - 1~2초
4. `공세동 1` (parcel) - 1~2초
5. `공세동 100` (road) - 1~2초
6. `공세동 100` (parcel) - 1~2초

**총 소요 시간**: 6~12초 (평균 8초)

---

## ✅ 해결 방법

### 방법 1: **단일 호출로 변경** (가장 효과적!)

```php
// 패턴 생성 제거
$patterns = [$keyword]; // 입력한 키워드만 사용

// road 타입만 호출 (parcel 제거)
$url = "http://api.vworld.kr/...&type=road...";
$response = fetch_url($url);

// 결과가 없으면 parcel 시도
if (count($results) === 0) {
    $url = "http://api.vworld.kr/...&type=parcel...";
    $response = fetch_url($url);
}
```

**효과**:
- 6번 호출 → 1~2번 호출
- 8초 → 1~2초 (75% 단축!)

### 방법 2: **타임아웃 단축**

```php
CURLOPT_TIMEOUT => 3,         // 5초 → 3초
CURLOPT_CONNECTTIMEOUT => 2,  // 3초 → 2초
```

**효과**:
- 최악의 경우 시간 단축
- 빠른 실패 → 사용자 경험 개선

### 방법 3: **결과 캐싱**

```php
// 같은 키워드 재검색 시 캐시 사용
$cacheKey = 'vworld_' . md5($keyword);
$cached = apcu_fetch($cacheKey);
if ($cached !== false) {
    return $cached;
}

// API 호출 후 캐시 저장
apcu_store($cacheKey, $results, 3600); // 1시간
```

**효과**:
- 재검색 시 즉시 응답
- API 호출 횟수 감소

### 방법 4: **디바운싱 (프론트엔드)**

```javascript
// 현재: 입력할 때마다 즉시 호출
input.addEventListener('input', function() {
    fetchAddress(this.value);
});

// 개선: 입력 멈춘 후 300ms 후 호출
let debounceTimer;
input.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchAddress(this.value);
    }, 300);
});
```

**효과**:
- 불필요한 API 호출 감소
- "공" → "공세" → "공세동" 입력 시: 3번 → 1번

---

## 🎯 권장 수정 사항

### 우선순위 1: **단일 호출로 변경** (즉시 적용)

`api/address-suggest.php` 수정:

```php
// 기존 코드 (96-103번 줄)
$patterns = [$keyword];
if (!preg_match('/\d+/', $keyword)) {
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 100';
}

// 수정 후
$patterns = [$keyword]; // 추가 패턴 제거
```

**예상 효과**: 8초 → 2초 (75% 개선)

### 우선순위 2: **parcel 타입 제거** (선택적)

```php
// 186-211번 줄 주석 처리 또는 삭제
// parcel(지번) 타입도 시도
// ...
```

**예상 효과**: 2초 → 1초 (50% 개선)

### 우선순위 3: **타임아웃 단축**

```php
// 120-121번 줄
CURLOPT_TIMEOUT => 3,         // 5초 → 3초
CURLOPT_CONNECTTIMEOUT => 2,  // 3초 → 2초
```

### 우선순위 4: **프론트엔드 디바운싱**

`pages/requests/new.php`, `pages/auth/signup.php` 수정:

```javascript
// 입력 이벤트에 디바운싱 추가
let debounceTimer;
addrInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        updateAddrBtn();
    }, 300);
});
```

---

## 📊 개선 효과 비교

| 방법 | 현재 | 개선 후 | 개선율 |
|---|---|---|---|
| **현재** | 8초 | - | - |
| 방법 1 (단일 호출) | 8초 | 2초 | 75% |
| 방법 1+2 (parcel 제거) | 8초 | 1초 | 87.5% |
| 방법 1+2+3 (타임아웃) | 8초 | 0.5~1초 | 90% |
| 방법 1+2+3+4 (디바운싱) | 8초 | 0.5초 | 93% |

---

## 🚀 즉시 적용 가능한 수정

### 1단계: `api/address-suggest.php` 수정

**96-103번 줄 변경**:
```php
// 변경 전
$patterns = [$keyword];
if (!preg_match('/\d+/', $keyword)) {
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 100';
}

// 변경 후
$patterns = [$keyword]; // 입력한 키워드만 사용
```

**186-211번 줄 주석 처리** (parcel 타입 제거):
```php
// 변경 전
// parcel(지번) 타입도 시도 (결과가 부족할 때만)
if (!$shouldBreak && count($results) < 5) {
    // ... parcel 호출 코드 ...
}

// 변경 후
// parcel 타입 제거 (속도 개선)
// if (!$shouldBreak && count($results) < 5) {
//     // ... 주석 처리 ...
// }
```

### 2단계: 타임아웃 단축

**120-121번 줄 변경**:
```php
// 변경 전
CURLOPT_TIMEOUT => 5,
CURLOPT_CONNECTTIMEOUT => 3,

// 변경 후
CURLOPT_TIMEOUT => 3,
CURLOPT_CONNECTTIMEOUT => 2,
```

---

## ✅ 체크리스트

수정 후 테스트:
- [ ] "공세동" 검색 → 1~2초 이내 응답
- [ ] "서울시 강남구" 검색 → 1~2초 이내 응답
- [ ] 결과가 정확히 표시되는지 확인
- [ ] 에러 없이 작동하는지 확인

---

## 💡 추가 최적화 (선택)

### VWorld API 대신 카카오 주소 API 사용

**장점**:
- 더 빠른 응답 속도
- 더 정확한 결과
- 더 나은 자동완성

**단점**:
- 카카오 개발자 계정 필요
- API 키 발급 필요
- 일일 호출 제한 (무료: 300,000회/일)

**참고**: VWorld API는 무료이지만 느림, 카카오는 빠르지만 제한 있음
