// PWA 아이콘 생성 스크립트
// Node.js에서 실행: node scripts/generate-icons.js

const fs = require('fs');
const path = require('path');

// 간단한 SVG를 기반으로 아이콘 생성 안내
console.log(`
PWA 아이콘 생성 안내:

1. 온라인 아이콘 생성기 사용:
   - https://realfavicongenerator.net/
   - https://www.pwabuilder.com/imageGenerator
   - 텍스트: "H" 또는 "행복안심동행"
   - 배경색: #2563eb (파란색)
   - 크기: 192x192, 512x512

2. 생성된 파일을 다음 위치에 저장:
   - public/pwa-192x192.png
   - public/pwa-512x512.png

3. 또는 기본 아이콘을 사용하려면 vite.config.js에서 아이콘 설정을 제거하세요.
`);
