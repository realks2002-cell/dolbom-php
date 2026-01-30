<template>
  <div>
    <h1 class="text-xl font-bold">설정</h1>
    <p class="mt-1 text-gray-600">알림 및 앱 설정을 관리합니다.</p>
    
    <div class="mt-6 space-y-4">
      <!-- 푸시 알림 설정 -->
      <div class="rounded-lg border bg-white p-6">
        <h2 class="text-lg font-semibold">푸시 알림</h2>
        <p class="mt-1 text-sm text-gray-600">새로운 서비스 요청 알림을 받습니다.</p>
        
        <div class="mt-4 flex items-center justify-between">
          <div>
            <p class="font-medium">알림 수신</p>
            <p class="text-sm text-gray-500">새 요청이 등록되면 알림을 받습니다</p>
          </div>
          <label class="relative inline-flex cursor-pointer items-center">
            <input
              v-model="notificationsEnabled"
              type="checkbox"
              class="peer sr-only"
              @change="toggleNotifications"
            />
            <div class="peer h-6 w-11 rounded-full bg-gray-200 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20"></div>
          </label>
        </div>
        
        <div v-if="tokenStatus" class="mt-4 rounded-lg border p-3" :class="tokenStatus.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
          <p class="text-sm" :class="tokenStatus.success ? 'text-green-700' : 'text-red-700'">
            {{ tokenStatus.message }}
          </p>
        </div>
        
        <div v-if="isHybridApp" class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
          <p class="text-sm text-blue-700">
            하이브리드 앱 환경에서 실행 중입니다. 알림은 자동으로 등록됩니다.
          </p>
        </div>
      </div>
      
      <!-- 앱 정보 -->
      <div class="rounded-lg border bg-white p-6">
        <h2 class="text-lg font-semibold">앱 정보</h2>
        <div class="mt-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">버전</span>
            <span class="font-medium">1.0.0</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">플랫폼</span>
            <span class="font-medium">{{ platform }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { registerDeviceToken } from '@/api'

const notificationsEnabled = ref(true)
const tokenStatus = ref(null)
const platform = ref('web')

const isHybridApp = computed(() => {
  return window.Capacitor !== undefined
})

onMounted(() => {
  // 플랫폼 감지
  if (window.Capacitor) {
    const device = window.Capacitor.getPlatform()
    platform.value = device === 'ios' ? 'ios' : device === 'android' ? 'android' : 'web'
  } else {
    platform.value = 'web'
  }
  
  // 로컬 스토리지에서 알림 설정 불러오기
  const saved = localStorage.getItem('notifications_enabled')
  if (saved !== null) {
    notificationsEnabled.value = saved === 'true'
  }
})

async function toggleNotifications() {
  localStorage.setItem('notifications_enabled', notificationsEnabled.value.toString())
  
  if (notificationsEnabled.value) {
    // 하이브리드 앱에서 토큰 등록
    if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.PushNotifications) {
      try {
        const { PushNotifications } = window.Capacitor.Plugins
        const registration = await PushNotifications.register()
        
        PushNotifications.addListener('registration', async (token) => {
          try {
            await registerDeviceToken(token.value, platform.value, '1.0.0')
            tokenStatus.value = {
              success: true,
              message: '알림이 활성화되었습니다.'
            }
          } catch (e) {
            tokenStatus.value = {
              success: false,
              message: '토큰 등록에 실패했습니다: ' + (e.message || '알 수 없는 오류')
            }
          }
        })
        
        PushNotifications.addListener('registrationError', (error) => {
          tokenStatus.value = {
            success: false,
            message: '푸시 알림 등록 오류: ' + (error.message || '알 수 없는 오류')
          }
        })
      } catch (e) {
        tokenStatus.value = {
          success: false,
          message: '푸시 알림 초기화 실패: ' + (e.message || '알 수 없는 오류')
        }
      }
    } else {
      tokenStatus.value = {
        success: false,
        message: '하이브리드 앱 환경이 아닙니다. 웹에서는 브라우저 알림 권한을 사용합니다.'
      }
    }
  } else {
    tokenStatus.value = {
      success: true,
      message: '알림이 비활성화되었습니다.'
    }
  }
  
  // 3초 후 상태 메시지 제거
  setTimeout(() => {
    tokenStatus.value = null
  }, 3000)
}
</script>
