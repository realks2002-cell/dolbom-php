# 호스팅 업로드 체크리스트

## ✅ 업로드해야 할 파일

### 1. Vue.js 앱 빌드 파일
**위치**: `manager-app/dist/` 폴더의 **모든 파일**

**업로드 위치**: 호스팅 서버의 `/manager-app/` 폴더

**파일 목록**:
- ✅ `dist/index.html` ← **중요! 빌드된 버전이어야 함**
- ✅ `dist/assets/` 폴더 전체
- ✅ `dist/manifest.webmanifest`
- ✅ `dist/registerSW.js`
- ✅ `dist/sw.js`
- ✅ `dist/workbox-xxxxx.js` (있다면)

### 2. .htaccess 파일
**위치**: `manager-app/.htaccess`

**업로드 위치**: 호스팅 서버의 `/manager-app/` 폴더

## ❌ 업로드하면 안 되는 파일

- ❌ `manager-app/index.html` (개발 모드용)
- ❌ `manager-app/src/` 폴더 (소스 코드)
- ❌ `manager-app/node_modules/` 폴더
- ❌ `manager-app/package.json` 등 개발 파일들

## 🔍 업로드 전 확인 사항

### dist/index.html 확인
빌드된 `dist/index.html` 파일을 열어서 확인:

**✅ 올바른 경우:**
```html
<script type="module" crossorigin src="/manager-app/assets/index-DSU6cSh6.js"></script>
```

**❌ 잘못된 경우 (업로드하면 안 됨):**
```html
<script type="module" src="/src/main.js"></script>
```

## 📋 업로드 후 확인

1. 브라우저에서 `https://travel23.mycafe24.com/manager-app/` 접속
2. 개발자 도구(F12) → Network 탭 확인
3. JavaScript 파일이 `/manager-app/assets/...` 경로로 로드되는지 확인
4. 404 오류가 없는지 확인

## 🚨 문제 해결

### 여전히 `/src/main.js` 404 오류가 발생하면:

1. **호스팅 서버의 `/manager-app/index.html` 파일 확인**
   - 파일을 다운로드하여 열어보기
   - `<script src="/src/main.js">`가 있으면 잘못된 파일
   - `<script src="/manager-app/assets/...">`가 있어야 함

2. **올바른 파일로 교체**
   - 로컬의 `manager-app/dist/index.html` 파일을 호스팅에 업로드
   - 기존 파일 덮어쓰기

3. **브라우저 캐시 지우기**
   - Ctrl + F5 (강력 새로고침)
   - 또는 개발자 도구 → Network 탭 → "Disable cache" 체크
