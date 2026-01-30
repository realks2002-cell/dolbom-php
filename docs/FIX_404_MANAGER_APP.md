# /manager-app/ 404 오류 해결 가이드

## 🔍 문제 원인

호스팅 서버에서 `https://travel23.mycafe24.com/manager-app/` 접속 시 404 오류가 발생하는 이유:

1. **루트 `.htaccess`가 모든 요청을 가로챔**
   - 루트의 `.htaccess`가 `/manager-app/` 경로도 PHP 라우터로 보냄
   - Vue.js 앱이 자체 라우팅을 처리할 수 없음

2. **서브디렉토리 `.htaccess` 설정 문제**
   - `manager-app/.htaccess` 파일이 없거나 잘못 설정됨

## ✅ 해결 방법

### 1. 루트 `.htaccess` 수정

**파일**: `.htaccess` (프로젝트 루트)

**수정 내용**:
```apache
# manager-app 폴더는 제외 (Vue.js 앱이 자체 라우팅 처리)
RewriteCond %{REQUEST_URI} !^/manager-app/
```

이렇게 하면 `/manager-app/` 경로는 PHP 라우터를 거치지 않고 직접 서브디렉토리로 전달됩니다.

### 2. 서브디렉토리 `.htaccess` 확인

**파일**: `manager-app/.htaccess`

**필수 내용**:
```apache
DirectoryIndex index.html

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} !^/api/
    RewriteRule . index.html [L]
</IfModule>
```

### 3. 파일 업로드 확인

호스팅 서버의 `/manager-app/` 폴더에 다음 파일들이 있는지 확인:

- ✅ `index.html` (빌드된 버전)
- ✅ `.htaccess`
- ✅ `assets/` 폴더
- ✅ `manifest.webmanifest`
- ✅ `registerSW.js`
- ✅ `sw.js`

## 📋 업로드할 파일

### 수정된 파일:
1. `.htaccess` (프로젝트 루트) - `/manager-app/` 경로 제외 추가
2. `manager-app/.htaccess` - DirectoryIndex 추가

### 빌드된 파일:
- `manager-app/dist/` 폴더의 모든 파일

## 🚀 업로드 후 테스트

1. `https://travel23.mycafe24.com/manager-app/` 접속
2. 정상적으로 Vue.js 앱이 로드되는지 확인
3. 개발자 도구(F12) → Network 탭에서 404 오류가 없는지 확인

## 🔧 추가 확인 사항

### Apache 설정 확인

카페24 호스팅에서 다음이 활성화되어 있어야 합니다:
- `mod_rewrite` 모듈
- `.htaccess` 파일 허용

일반적으로 카페24 호스팅에서는 기본적으로 활성화되어 있습니다.

### 파일 권한 확인

- `.htaccess` 파일: 읽기 권한 (644)
- `index.html` 파일: 읽기 권한 (644)
- `assets/` 폴더: 읽기 권한 (755)
