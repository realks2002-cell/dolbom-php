/**
 * Service Worker for Hangbok77 매니저 PWA
 */

const CACHE_NAME = 'hangbok77-manager-v1';
const urlsToCache = [
  '/',
  '/manager/dashboard',
  '/assets/css/custom.css',
  '/assets/js/main.js',
  'https://cdn.tailwindcss.com'
];

// 설치 이벤트
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('캐시 열기');
        return cache.addAll(urlsToCache);
      })
      .catch((error) => {
        console.error('캐시 설치 실패:', error);
      })
  );
  self.skipWaiting();
});

// 활성화 이벤트
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('오래된 캐시 삭제:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// fetch 이벤트 - 네트워크 우선, 실패 시 캐시 사용
self.addEventListener('fetch', (event) => {
  // GET 요청만 캐싱
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // 응답이 유효한지 확인
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // 응답 복제 (한 번만 사용 가능하므로)
        const responseToCache = response.clone();

        caches.open(CACHE_NAME)
          .then((cache) => {
            cache.put(event.request, responseToCache);
          });

        return response;
      })
      .catch(() => {
        // 네트워크 실패 시 캐시에서 반환
        return caches.match(event.request);
      })
  );
});

// 푸시 알림 수신 이벤트
self.addEventListener('push', (event) => {
  console.log('푸시 알림 수신:', event);
  
  let title = 'Hangbok77 매니저';
  let body = '새로운 알림이 있습니다.';
  let icon = '/assets/icons/icon-192x192.png';
  let badge = '/assets/icons/icon-192x192.png';
  let data = {};
  
  if (event.data) {
    try {
      const payload = event.data.json();
      title = payload.notification?.title || payload.title || title;
      body = payload.notification?.body || payload.body || body;
      data = payload.data || payload;
    } catch (e) {
      // 텍스트 형식인 경우
      body = event.data.text() || body;
    }
  }
  
  const options = {
    body: body,
    icon: icon,
    badge: badge,
    vibrate: [200, 100, 200],
    tag: 'hangbok77-notification',
    requireInteraction: false,
    data: data,
    actions: [
      {
        action: 'open',
        title: '확인'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// 알림 클릭 이벤트
self.addEventListener('notificationclick', (event) => {
  console.log('알림 클릭:', event);
  
  event.notification.close();
  
  const data = event.notification.data || {};
  const requestId = data.request_id;
  
  // 대시보드 URL 생성
  let url = '/manager/dashboard';
  if (requestId) {
    url += '?request_id=' + encodeURIComponent(requestId);
  }
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // 이미 열려있는 창이 있으면 포커스
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url.includes('/manager/dashboard') && 'focus' in client) {
            return client.focus();
          }
        }
        // 없으면 새 창 열기
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});
