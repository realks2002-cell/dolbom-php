# 404 오류 해결 가이드

## 🔍 문제 진단

### 1. 브라우저 개발자 도구 확인
1. F12 키를 눌러 개발자 도구 열기
2. **Network** 탭 확인
3. 404 오류가 발생하는 파일 경로 확인
   - 예: `/assets/index-xxxxx.js` → 404
   - 예: `/manager-app/assets/index-xxxxx.js` → 404

### 2. 빌드된 파일 확인
`manager-app/dist/index.html` 파일을 열어서 스크립트 경로 확인:

**올바른 경우:**
```html
<script type="module" src="/manager-app/assets/index-xxxxx.js"></script>
```

**잘못된 경우:**
```html
<script type="module" src="/assets/index-xxxxx.js"></script>
```

## ✅ 해결 방법

### 방법 1: 올바르게 다시 빌드

**Windows PowerShell:**
```powershell
cd manager-app
$env:BASE="/manager-app/"
npm run build
```

**빌드 후 확인:**
1. `dist/index.html` 파일 열기
2. 스크립트 경로가 `/manager-app/assets/...`로 시작하는지 확인
3. 맞다면 `dist/` 폴더의 모든 파일을 호스팅에 업로드

### 방법 2: 빌드된 파일 수정 (임시 해결책)

만약 이미 빌드된 파일이 있다면:

1. `dist/index.html` 파일 열기
2. 모든 `/assets/` 경로를 `/manager-app/assets/`로 변경
3. 모든 `/`로 시작하는 경로에 `/manager-app` 추가

**예시:**
```html
<!-- 변경 전 -->
<script type="module" src="/assets/index-xxxxx.js"></script>
<link rel="stylesheet" href="/assets/index-xxxxx.css">

<!-- 변경 후 -->
<script type="module" src="/manager-app/assets/index-xxxxx.js"></script>
<link rel="stylesheet" href="/manager-app/assets/index-xxxxx.css">
```

### 방법 3: .htaccess 확인

호스팅 서버의 `/manager-app/.htaccess` 파일이 있는지 확인:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} !^/api/
    RewriteRule . index.html [L]
</IfModule>
```

## 📋 체크리스트

- [ ] 빌드 시 `BASE=/manager-app/` 환경 변수 설정했는가?
- [ ] `dist/index.html`의 스크립트 경로가 `/manager-app/assets/...`로 시작하는가?
- [ ] 호스팅 서버의 `/manager-app/` 폴더에 `.htaccess` 파일이 있는가?
- [ ] `dist/` 폴더의 모든 파일이 호스팅에 업로드되었는가?
- [ ] 브라우저 캐시를 지웠는가? (Ctrl+F5)

## 🚨 여전히 안 되면

1. **브라우저 콘솔 확인**: F12 → Console 탭에서 JavaScript 오류 확인
2. **네트워크 탭 확인**: 어떤 파일이 404인지 정확히 확인
3. **호스팅 파일 확인**: FTP로 접속하여 실제 파일이 업로드되었는지 확인
4. **경로 확인**: 호스팅 서버의 실제 경로 구조 확인
