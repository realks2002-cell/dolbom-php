# main.js 404 오류 빠른 해결

## 🔍 문제 확인

브라우저 개발자 도구에서:
- `main.js` → 404 오류 ❌
- `firebase-app.js` → 200 (정상) ✅
- `firebase-messaging.js` → 200 (정상) ✅

## ✅ 해결 방법

### 문제 원인
호스팅 서버의 `/manager-app/index.html` 파일이 **개발 모드 파일**입니다.

**잘못된 내용 (42번째 줄):**
```html
<script type="module" src="/src/main.js"></script>
```

### 해결 단계

1. **로컬 파일 확인**
   - 파일: `c:\xampp\htdocs\dolbom_php\manager-app\dist\index.html`
   - 이 파일을 열어서 18번째 줄 확인:
   ```html
   <script type="module" crossorigin src="/manager-app/assets/index-DSU6cSh6.js"></script>
   ```
   - 이 내용이면 올바른 파일입니다 ✅

2. **호스팅 서버에 업로드**
   - FTP 또는 파일 관리자로 호스팅 서버 접속
   - `/manager-app/` 폴더로 이동
   - 기존 `index.html` 파일 **삭제** 또는 **백업**
   - 로컬의 `manager-app/dist/index.html` 파일 업로드
   - 파일명이 정확히 `index.html`인지 확인

3. **브라우저 캐시 지우기**
   - Ctrl + Shift + Delete → 캐시 삭제
   - 또는 개발자 도구(F12) → Network 탭 → "Disable cache" 체크

4. **테스트**
   - `https://travel23.mycafe24.com/manager-app/` 접속
   - 개발자 도구(F12) → Network 탭 확인
   - `main.js` 404 오류가 사라졌는지 확인
   - 대신 `/manager-app/assets/index-DSU6cSh6.js` 파일이 로드되는지 확인

## 📋 파일 비교

### 개발 모드 파일 (업로드하면 안 됨)
```
파일: manager-app/index.html
42번째 줄: <script type="module" src="/src/main.js"></script>
```

### 빌드된 파일 (업로드해야 함)
```
파일: manager-app/dist/index.html
18번째 줄: <script type="module" crossorigin src="/manager-app/assets/index-DSU6cSh6.js"></script>
```

## ⚠️ 중요

- **절대 업로드하지 말 것**: `manager-app/index.html` (개발 모드)
- **반드시 업로드할 것**: `manager-app/dist/index.html` (빌드된 파일)

## ✅ 확인 방법

업로드 후 브라우저에서:
1. 페이지 소스 보기 (우클릭 → "페이지 소스 보기")
2. 검색: `main.js`
3. 결과:
   - ❌ `<script src="/src/main.js">` → 잘못된 파일
   - ✅ `<script src="/manager-app/assets/index-DSU6cSh6.js">` → 올바른 파일
