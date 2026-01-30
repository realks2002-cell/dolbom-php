# 접속 URL 가이드

## 🚀 서버 실행 방법에 따른 접속 URL

### 방법 1: PHP 내장 서버 (권장)

터미널에서 실행:
```bash
php -S localhost:8000 router.php
```

또는 `run-local.bat` 파일을 더블클릭

**접속 URL**:
- 메인페이지: `http://localhost:8000/`
- 매니저 로그인: `http://localhost:8000/manager/login`
- 관리자 로그인: `http://localhost:8000/admin.php`

---

### 방법 2: XAMPP Apache 사용

XAMPP에서 Apache를 시작한 후:

**접속 URL**:
- 메인페이지: `http://localhost/dolbom_php/`
- 매니저 로그인: `http://localhost/dolbom_php/manager/login`
- 관리자 로그인: `http://localhost/dolbom_php/admin.php`

---

## ⚠️ 문제 해결

### 404 오류가 발생하는 경우

1. **PHP 내장 서버를 사용하는 경우**:
   - 반드시 `router.php`를 사용해야 합니다
   - ❌ 잘못된 명령: `php -S localhost:8000`
   - ✅ 올바른 명령: `php -S localhost:8000 router.php`

2. **XAMPP를 사용하는 경우**:
   - Apache의 `mod_rewrite` 모듈이 활성화되어 있어야 합니다
   - `.htaccess` 파일이 존재하는지 확인
   - `httpd.conf`에서 `AllowOverride All` 설정 확인

3. **직접 접근 (임시 해결책)**:
   ```
   http://localhost:8000/index.php?route=manager/login
   ```

---

## 📋 주요 페이지 URL 목록

### 고객 페이지
- 메인: `http://localhost:8000/`
- 로그인: `http://localhost:8000/auth/login`
- 회원가입: `http://localhost:8000/auth/signup`
- 서비스 요청: `http://localhost:8000/requests/new`
- 예약 목록: `http://localhost:8000/bookings`

### 매니저 페이지
- 로그인: `http://localhost:8000/manager/login`
- 회원가입: `http://localhost:8000/manager/signup`
- 대시보드: `http://localhost:8000/manager/dashboard`
- 매니저 지원: `http://localhost:8000/manager/recruit`

### 관리자 페이지
- 로그인: `http://localhost:8000/admin.php`
- 대시보드: `http://localhost:8000/admin`
- 회원 관리: `http://localhost:8000/admin/users`
- 매니저 관리: `http://localhost:8000/admin/managers`

---

## 🔍 디버깅

### 현재 라우트 확인
브라우저 개발자 도구(F12) → Network 탭에서 실제 요청 URL 확인

### 라우트 테스트
```
http://localhost:8000/index.php?route=manager/login
```
이 URL로 접속이 되면 라우팅 문제입니다.

### 파일 존재 확인
터미널에서:
```bash
dir pages\manager\login.php
```
파일이 존재하는지 확인
