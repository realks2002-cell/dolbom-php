# PWA 아이콘 생성 가이드

## 문제
PWA 아이콘 파일(`pwa-192x192.png`, `pwa-512x512.png`)이 없어서 브라우저에서 오류가 발생합니다.

## 해결 방법

### 방법 1: 온라인 아이콘 생성기 사용 (권장)

1. **PWA Builder Image Generator** 사용:
   - https://www.pwabuilder.com/imageGenerator
   - 텍스트: "H" 또는 "Hangbok77"
   - 배경색: #2563eb (파란색)
   - 크기: 192x192, 512x512 생성

2. **RealFaviconGenerator** 사용:
   - https://realfavicongenerator.net/
   - 이미지 업로드 또는 텍스트로 생성
   - 다양한 크기 자동 생성

3. 생성된 파일을 다음 위치에 저장:
   ```
   manager-app/public/pwa-192x192.png
   manager-app/public/pwa-512x512.png
   ```

### 방법 2: 간단한 아이콘 생성 (Node.js)

```bash
# canvas 패키지 설치
npm install canvas --save-dev

# 아이콘 생성 스크립트 실행
node scripts/generate-icons-simple.js
```

### 방법 3: 임시 해결 (아이콘 없이 사용)

`vite.config.js`에서 아이콘 설정을 제거하거나 빈 배열로 설정:

```javascript
icons: [] // 임시로 빈 배열
```

하지만 이 경우 PWA 설치 시 기본 아이콘이 표시됩니다.

## 추천 방법

**온라인 아이콘 생성기 사용**이 가장 빠르고 간단합니다:
1. https://www.pwabuilder.com/imageGenerator 접속
2. 텍스트 "H" 입력, 배경색 #2563eb 선택
3. 192x192, 512x512 크기 다운로드
4. `public` 폴더에 저장
5. 재배포

## 파일 위치

```
manager-app/
  └── public/
      ├── pwa-192x192.png  (필수)
      └── pwa-512x512.png  (필수)
```

## 재배포

아이콘 파일 추가 후:
```bash
npm run build
vercel --prod --yes
```

또는 GitHub에 푸시하면 Vercel이 자동으로 재배포합니다.
