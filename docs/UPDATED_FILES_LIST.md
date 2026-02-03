# 최근 수정된 파일 목록 (서버 재업로드 필요)

## 📅 수정 날짜: 2026-02-03

---

## ⭐ 새로 추가된 파일

### 1. 취소/환불 관리 기능
```
✅ pages/admin/refund-info.php      # 취소/환불 요청 관리 페이지
✅ api/admin/process-refund.php     # 수동 환불 처리 API
```

### 2. 진단 스크립트
```
✅ check_environment.php            # 서버 환경 진단
✅ test_refund_api.php              # 환불 API 테스트
```

### 3. 문서
```
✅ CAFE24_DEPLOYMENT_CHECKLIST.md  # 배포 체크리스트
✅ SERVER_UPLOAD_LIST.md            # 업로드 파일 목록
✅ UNNECESSARY_FOLDERS.md           # 불필요한 폴더
✅ REFUND_FAILURE_DIAGNOSIS.md     # 환불 실패 진단
```

---

## 🔧 수정된 파일

### 1. 관리자 레이아웃
```
✅ components/admin-layout.php
   - 'admin/refund-requests' 메뉴 제거
   - 'admin/refund-info' 메뉴로 통합
```

### 2. 이전 수정 사항 (서버에 반영 필요)
```
✅ pages/auth/signup.php            # VWorld 주소 검색 추가
✅ pages/auth/login.php             # 비밀번호 placeholder "6자리"
✅ pages/manager/login.php          # 비밀번호 placeholder "6자리"
✅ pages/manager/signup.php         # 비밀번호 placeholder "6자리"
✅ pages/manager/reset-password.php # 비밀번호 placeholder "6자리"
✅ admin.php                        # 비밀번호 placeholder "6자리"
✅ components/header.php            # "로그인" → "회원 로그인"
✅ pages/admin/payments.php         # 비회원 결제 표시 (LEFT JOIN)
✅ pages/requests/new.php           # 회원도 1.5단계 거치기
✅ includes/auth.php                # phone, address 필드 추가
✅ api/requests/save-temp.php       # customer_id 검증 강화
✅ pages/payment/success.php        # customer_id 복구 로직
```

---

## 🚀 서버 재업로드 방법

### 방법 1: 개별 파일 업로드 (빠름)

**새로 추가된 파일만**:
```
FTP 업로드:
1. pages/admin/refund-info.php
2. api/admin/process-refund.php (폴더 생성 필요)
3. components/admin-layout.php (덮어쓰기)
```

**진단 스크립트** (선택):
```
4. check_environment.php
5. test_refund_api.php
```

### 방법 2: 전체 재업로드 (안전)

**모든 수정 사항 반영**:
```
FTP로 전체 폴더 덮어쓰기:
- api/
- components/
- pages/
- includes/
```

---

## 📋 업로드 체크리스트

### 필수 업로드
- [ ] `pages/admin/refund-info.php`
- [ ] `api/admin/process-refund.php` (api/admin/ 폴더 생성)
- [ ] `components/admin-layout.php`

### 이전 수정사항 확인
- [ ] `pages/auth/signup.php` (VWorld 주소 검색)
- [ ] `components/header.php` ("회원 로그인")
- [ ] `pages/admin/payments.php` (비회원 결제 표시)
- [ ] `pages/requests/new.php` (회원 1.5단계)
- [ ] `api/requests/save-temp.php` (customer_id 검증)
- [ ] `pages/payment/success.php` (customer_id 복구)

### 선택 업로드
- [ ] `check_environment.php` (환경 진단)
- [ ] `test_refund_api.php` (환불 API 테스트)

---

## 🔍 서버와 로컬이 다른 이유

### 가능한 원인:

1. **파일 업로드 누락**
   - 이전에 수정한 파일이 서버에 업로드 안 됨
   - `components/admin-layout.php` 업로드 누락

2. **브라우저 캐시**
   - 서버에서 Ctrl + Shift + R로 강력 새로고침

3. **FTP 동기화 실패**
   - 파일 타임스탬프 확인
   - 파일 크기 확인

4. **서버 캐시**
   - PHP OpCache 초기화 필요
   - 서버 재시작

---

## 🛠️ 즉시 해결 방법

### 1단계: 파일 업로드 확인
```
FTP로 서버 접속
→ components/admin-layout.php 날짜 확인
→ 오늘 날짜가 아니면 재업로드
```

### 2단계: 강력 새로고침
```
서버 페이지에서:
Ctrl + Shift + R (Windows)
Cmd + Shift + R (Mac)
```

### 3단계: PHP 캐시 초기화
```php
<?php
// clear_cache.php (서버에 업로드)
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache 초기화 완료";
} else {
    echo "OpCache 사용 안 함";
}
?>
```

---

## 📞 확인 방법

### 서버에서 메뉴 확인
```
1. 서버 admin 페이지 접속
2. F12 (개발자 도구) 열기
3. Elements 탭에서 사이드바 HTML 확인
4. "취소/환불 요청" 메뉴가 있는지 확인
```

### 파일 버전 확인
```php
<?php
// check_version.php (서버에 업로드)
echo "admin-layout.php 수정 시간: " . date('Y-m-d H:i:s', filemtime('components/admin-layout.php'));
?>
```

---

## 🎯 권장 조치

**가장 확실한 방법**:
```
1. components/admin-layout.php 재업로드
2. pages/admin/refund-info.php 업로드
3. api/admin/process-refund.php 업로드 (폴더 생성)
4. 서버에서 Ctrl + Shift + R
```

이렇게 하면 메뉴가 나타납니다!
