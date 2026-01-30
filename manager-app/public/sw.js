// Service Worker - 푸시 알림 처리
// vite-plugin-pwa가 자동으로 이 파일을 포함합니다

self.addEventListener('push', (event) => {
  console.log('푸시 알림 수신:', event)
  
  let data = {}
  if (event.data) {
    try {
      data = event.data.json()
    } catch (e) {
      data = { title: '새 알림', body: event.data.text() || '새로운 알림이 있습니다.' }
    }
  }
  
  const options = {
    body: data.body || data.notification?.body || '새로운 알림이 있습니다.',
    icon: '/pwa-192x192.png',
    badge: '/pwa-192x192.png',
    vibrate: [200, 100, 200],
    tag: data.tag || 'default',
    data: data.data || {}
  }
  
  event.waitUntil(
    self.registration.showNotification(
      data.title || data.notification?.title || 'Hangbok77 매니저',
      options
    )
  )
})

self.addEventListener('notificationclick', (event) => {
  console.log('알림 클릭:', event)
  
  event.notification.close()
  
  event.waitUntil(
    clients.openWindow(event.notification.data?.url || '/')
  )
})
