# PWA 설정 가이드

## 완료된 작업
- ✅ vite-plugin-pwa 패키지 추가
- ✅ PWA manifest 설정
- ✅ Service Worker 설정
- ✅ 웹 푸시 알림 기본 구조

## 다음 단계

### 1. Firebase 웹 앱 추가

1. Firebase Console (https://console.firebase.google.com) 접속
2. 프로젝트 `dolbom-6ff79` 선택
3. 프로젝트 설정 → "앱 추가" → **웹** 선택
4. 앱 닉네임: `Hangbok77 Manager Web`
5. Firebase Hosting 설정은 건너뛰기
6. Firebase SDK 설정 코드 복사

### 2. Firebase 설정 업데이트

`index.html`의 Firebase 설정을 업데이트하세요:

```javascript
const firebaseConfig = {
  apiKey: "실제_API_KEY",
  authDomain: "dolbom-6ff79.firebaseapp.com",
  projectId: "dolbom-6ff79",
  storageBucket: "dolbom-6ff79.appspot.com",
  messagingSenderId: "133410524921",
  appId: "실제_APP_ID"
};
```

### 3. VAPID 키 생성 및 설정

1. Firebase Console → 프로젝트 설정 → 클라우드 메시징
2. "웹 푸시 인증서" 섹션에서 키 쌍 생성
3. 생성된 VAPID 키를 복사
4. `index.html`의 `vapidKey`에 설정

### 4. PWA 아이콘 생성

`public` 폴더에 다음 아이콘 파일을 추가하세요:
- `pwa-192x192.png` (192x192 픽셀)
- `pwa-512x512.png` (512x512 픽셀)

온라인 도구 사용:
- https://realfavicongenerator.net/
- https://www.pwabuilder.com/imageGenerator

### 5. 패키지 설치 및 빌드

```powershell
cd c:\xampp\htdocs\dolbom_php\manager-app
npm install
npm run build
```

### 6. 테스트

1. 빌드된 앱을 웹 서버에 배포 (HTTPS 필요)
2. 브라우저에서 접속
3. 브라우저 주소창에 "설치" 아이콘 표시 확인
4. "홈 화면에 추가" 클릭하여 설치
5. 푸시 알림 권한 요청 확인

## 참고사항

- **HTTPS 필수**: PWA와 푸시 알림은 HTTPS 환경에서만 작동합니다
- **로컬 테스트**: `localhost`는 HTTPS 없이도 작동합니다
- **프로덕션**: 실제 배포 시 HTTPS 인증서가 필요합니다

## 문제 해결

### 푸시 알림이 작동하지 않음
1. HTTPS 사용 확인
2. Service Worker 등록 확인 (브라우저 개발자 도구 → Application → Service Workers)
3. Firebase 설정 확인
4. VAPID 키 설정 확인

### PWA 설치 옵션이 나타나지 않음
1. manifest.json이 올바르게 생성되었는지 확인
2. HTTPS 사용 확인
3. 브라우저 호환성 확인 (Chrome, Edge 권장)
