# 매니저 앱 실행 방법

## 1. 의존성 설치

터미널에서 다음 명령어 실행:

```bash
cd manager-app
npm install
```

## 2. 개발 서버 실행

```bash
npm run dev
```

## 3. 접속

브라우저에서 다음 주소로 접속:
- **http://localhost:3000**

## 4. 주의사항

- PHP 서버가 `http://localhost:8000`에서 실행 중이어야 합니다.
- `/api` 요청은 자동으로 `http://localhost:8000`으로 프록시됩니다.

## 문제 해결

### 포트가 이미 사용 중인 경우
다른 포트를 사용하려면 `vite.config.js`의 `port` 값을 변경하세요.

### node_modules 오류
```bash
rm -rf node_modules
npm install
```

### npm이 설치되지 않은 경우
Node.js를 먼저 설치하세요: https://nodejs.org/
