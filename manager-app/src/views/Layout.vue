<template>
  <div class="min-h-screen bg-gray-50 font-sans">
    <header class="sticky top-0 z-50 border-b bg-white">
      <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-4">
        <router-link to="/" class="text-lg font-bold">행복안심동행 매니저</router-link>
        <nav class="flex items-center gap-2">
          <router-link
            to="/requests"
            class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-700 hover:bg-gray-100"
          >
            새 요청
          </router-link>
          <router-link
            to="/applications"
            class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-700 hover:bg-gray-100"
          >
            지원 현황
          </router-link>
          <router-link
            to="/schedule"
            class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-700 hover:bg-gray-100"
          >
            내 일정
          </router-link>
          <router-link
            to="/settings"
            class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-700 hover:bg-gray-100"
            aria-label="설정"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </router-link>
          <span class="ml-2 text-sm text-gray-600">{{ user?.name }} 님</span>
          <button
            type="button"
            class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-3 text-sm text-gray-600 hover:bg-gray-100"
            @click="logout"
          >
            로그아웃
          </button>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-4xl px-4 py-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { getStoredUser, logout as apiLogout, fetchMe, setStoredUser, registerDeviceToken } from '@/api'

const router = useRouter()
const user = ref(getStoredUser())

onMounted(async () => {
  if (!user.value) {
    try {
      const data = await fetchMe()
      user.value = data.user
      if (data.user) setStoredUser(data.user)
    } catch (e) {
      if (e.status === 401) apiLogout()
      router.replace('/login')
      return
    }
  }
  
  // 푸시 알림 초기화
  await initPushNotifications()
})

async function initPushNotifications() {
  // 하이브리드 앱 (Capacitor)
  if (Capacitor.isNativePlatform()) {
    try {
      // 푸시 알림 권한 요청
      let permStatus = await PushNotifications.checkPermissions()
      
      if (permStatus.receive === 'prompt') {
        permStatus = await PushNotifications.requestPermissions()
      }
      
      if (permStatus.receive !== 'granted') {
        console.warn('푸시 알림 권한이 거부되었습니다.')
        return
      }
      
      // 푸시 알림 등록
      await PushNotifications.register()
      
      // 토큰 수신 리스너
      PushNotifications.addListener('registration', async (token) => {
        try {
          const platform = Capacitor.getPlatform() === 'ios' ? 'ios' : 'android'
          await registerDeviceToken(token.value, platform, '1.0.0')
          console.log('FCM 토큰 등록 완료:', token.value)
        } catch (e) {
          console.error('토큰 등록 실패:', e)
        }
      })
      
      // 등록 오류 리스너
      PushNotifications.addListener('registrationError', (error) => {
        console.error('푸시 알림 등록 오류:', error)
      })
      
      // 알림 수신 리스너
      PushNotifications.addListener('pushNotificationReceived', (notification) => {
        console.log('푸시 알림 수신:', notification)
      })
      
      // 알림 탭 리스너
      PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
        console.log('푸시 알림 탭:', notification)
      })
    } catch (e) {
      console.error('푸시 알림 초기화 실패:', e)
    }
  } 
  // PWA 웹 푸시 알림
  else if ('serviceWorker' in navigator && 'Notification' in window) {
    try {
      // Service Worker 등록 확인
      const registration = await navigator.serviceWorker.ready
      
      // 푸시 알림 권한 요청
      let permission = Notification.permission
      if (permission === 'default') {
        permission = await Notification.requestPermission()
      }
      
      if (permission !== 'granted') {
        console.warn('푸시 알림 권한이 거부되었습니다.')
        return
      }
      
      // Firebase Messaging이 로드되었는지 확인
      if (window.firebaseMessaging && window.firebaseGetToken) {
        try {
          // VAPID 키는 서버에서 받아와야 함 (일단 빈 문자열로 시도)
          const token = await window.firebaseGetToken(window.firebaseMessaging, {
            vapidKey: '', // Firebase Console에서 생성한 VAPID 키 필요
            serviceWorkerRegistration: registration
          })
          
          if (token) {
            await registerDeviceToken(token, 'web', '1.0.0')
            console.log('웹 FCM 토큰 등록 완료:', token)
          }
        } catch (e) {
          console.error('Firebase 토큰 가져오기 실패:', e)
          // Firebase 없이 Web Push API 직접 사용
          await initWebPushDirect(registration)
        }
      } else {
        // Firebase 없이 Web Push API 직접 사용
        await initWebPushDirect(registration)
      }
      
      // 포그라운드 알림 수신 리스너 (Firebase)
      if (window.firebaseOnMessage) {
        window.firebaseOnMessage(window.firebaseMessaging, (payload) => {
          console.log('포그라운드 알림 수신:', payload)
          // 알림 표시
          if (Notification.permission === 'granted') {
            new Notification(payload.notification?.title || '새 알림', {
              body: payload.notification?.body || '',
              icon: '/pwa-192x192.png',
              badge: '/pwa-192x192.png'
            })
          }
        })
      }
    } catch (e) {
      console.error('웹 푸시 알림 초기화 실패:', e)
    }
  }
}

async function initWebPushDirect(registration) {
  try {
    // 서버에서 VAPID 공개 키를 받아와야 함
    // 일단 기본적인 구조만 설정
    console.log('Web Push API 직접 사용 준비 중...')
    // VAPID 키는 서버 API에서 받아와야 합니다
  } catch (e) {
    console.error('Web Push 직접 초기화 실패:', e)
  }
}

function logout() {
  apiLogout()
  router.replace('/login')
}
</script>
