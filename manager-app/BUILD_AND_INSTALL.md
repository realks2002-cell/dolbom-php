# 📱 Hangbok77 매니저 앱 스마트폰 설치 가이드

스마트폰에 직접 설치할 수 있는 개발용 APK 빌드 방법입니다.

## 🚀 빠른 시작 (Android)

### 1단계: 의존성 설치

```bash
cd manager-app
npm install
```

### 2단계: Capacitor 초기화 (처음 한 번만)

```bash
npx cap init
```

입력할 정보:
- **App name**: Hangbok77 매니저
- **App ID**: com.hangbok77.manager
- **Web dir**: dist

### 3단계: Android 플랫폼 추가

```bash
npm run cap:add:android
```

### 4단계: Firebase 설정 (필수)

1. Firebase Console (https://console.firebase.google.com) 접속
2. 프로젝트 선택 → 프로젝트 설정
3. "앱 추가" → Android 선택
4. 패키지 이름: `com.hangbok77.manager`
5. `google-services.json` 파일 다운로드
6. 다운로드한 파일을 `manager-app/android/app/` 폴더에 복사

### 5단계: API 서버 주소 설정

스마트폰에서 접근 가능한 API 서버 주소를 설정해야 합니다.

**방법 1: .env 파일 생성 (권장)**

`manager-app/.env` 파일 생성:
```env
VITE_API_BASE=http://YOUR_COMPUTER_IP:8000
```

예시:
```env
VITE_API_BASE=http://192.168.0.100:8000
```

**방법 2: 빌드 시 환경 변수 지정**

```bash
VITE_API_BASE=http://192.168.0.100:8000 npm run build
```

> **참고**: 컴퓨터의 IP 주소 확인 방법:
> - Windows: `ipconfig` 실행 → IPv4 주소 확인
> - Mac/Linux: `ifconfig` 또는 `ip addr` 실행

### 6단계: 웹 앱 빌드

```bash
npm run build
```

### 7단계: Capacitor 동기화

```bash
npm run cap:sync
```

### 8단계: Android Studio에서 빌드

```bash
npm run cap:open:android
```

Android Studio가 열리면:

1. **Build > Make Project** 실행 (⌘F9 / Ctrl+F9)
2. **Build > Build Bundle(s) / APK(s) > Build APK(s)** 선택
3. 빌드 완료 후 APK 파일 위치 확인:
   - `android/app/build/outputs/apk/debug/app-debug.apk`

### 9단계: 스마트폰에 설치

#### 방법 1: USB 디버깅 (권장)

1. 스마트폰에서 **설정 > 휴대전화 정보** 접속
2. **빌드 번호**를 7번 연속 탭하여 개발자 옵션 활성화
3. **설정 > 개발자 옵션**에서 **USB 디버깅** 활성화
4. USB로 컴퓨터에 연결
5. Android Studio에서 실행 버튼 클릭 (▶) 또는:
   ```bash
   adb install android/app/build/outputs/apk/debug/app-debug.apk
   ```

#### 방법 2: APK 파일 직접 설치

1. `app-debug.apk` 파일을 스마트폰으로 전송 (이메일, 클라우드 등)
2. 스마트폰에서 **설정 > 보안** → **알 수 없는 출처** 설치 허용
3. 전송받은 APK 파일을 탭하여 설치

---

## 🔔 푸시 알림 설정 확인

### 앱 설치 후 확인 사항

1. 앱 실행 후 매니저 계정으로 로그인
2. 로그인 시 자동으로 FCM 토큰이 등록됩니다
3. 푸시 알림 권한 요청 팝업이 나타나면 **허용** 선택
4. 테스트: `http://YOUR_COMPUTER_IP:8000/test/push-notification` 접속
5. "모든 매니저에게 전송" 버튼 클릭하여 알림 수신 확인

---

## 📝 환경 변수 설정

### 개발 환경 (로컬 네트워크)

`manager-app/.env` 파일:
```env
VITE_API_BASE=http://192.168.0.100:8000
```

### 프로덕션 환경 (웹 호스팅)

`manager-app/.env.production` 파일:
```env
VITE_API_BASE=https://your-api-domain.com
```

---

## 🛠️ 빌드 스크립트

### 전체 빌드 프로세스 (한 번에 실행)

```bash
# 의존성 설치
npm install

# 웹 앱 빌드
npm run build

# Capacitor 동기화
npm run cap:sync

# Android Studio 열기
npm run cap:open:android
```

### 간편 빌드 스크립트

`package.json`에 이미 추가된 스크립트:
- `npm run cap:build` - 빌드 + 동기화
- `npm run cap:sync` - 동기화만
- `npm run cap:open:android` - Android Studio 열기

---

## ⚠️ 문제 해결

### Android Studio가 열리지 않음

```bash
# 수동으로 Android Studio 열기
cd android
# 또는
code android
```

### Firebase 설정 파일이 없음

- `google-services.json` 파일이 `android/app/` 폴더에 있어야 합니다
- Firebase Console에서 다운로드하여 복사하세요

### 앱에서 API 연결 실패

1. 컴퓨터와 스마트폰이 같은 Wi-Fi 네트워크에 연결되어 있는지 확인
2. 방화벽에서 포트 8000이 열려있는지 확인
3. `.env` 파일의 `VITE_API_BASE`가 올바른 IP 주소인지 확인

### 푸시 알림이 작동하지 않음

1. Firebase Console에서 `google-services.json` 파일이 최신인지 확인
2. 앱에서 알림 권한이 허용되었는지 확인
3. `http://YOUR_COMPUTER_IP:8000/test/push-notification`에서 토큰이 등록되었는지 확인

---

## 📱 iOS 빌드 (선택사항)

iOS는 macOS와 Xcode가 필요합니다.

```bash
npm run cap:add:ios
npm run cap:open:ios
```

Xcode에서 빌드 및 실행하세요.

---

## 🎯 다음 단계

1. 앱 설치 후 로그인 테스트
2. 푸시 알림 수신 테스트
3. 실제 서비스 요청으로 알림 테스트

문제가 발생하면 `docs/PUSH_NOTIFICATION_TEST.md`를 참조하세요.
