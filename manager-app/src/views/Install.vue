<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 font-sans">
    <div class="w-full max-w-md text-center">
      <div class="mb-8">
        <svg class="w-24 h-24 mx-auto text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <h1 class="text-3xl font-bold mb-2">Hangbok77 매니저 앱</h1>
        <p class="text-gray-600">스마트폰에 설치하여 더 편리하게 사용하세요</p>
      </div>

      <div v-if="!isInstalled" class="space-y-4">
        <button
          v-if="showInstallButton"
          @click="installApp"
          class="min-h-[44px] w-full bg-green-600 text-white rounded-lg font-medium text-lg hover:opacity-90 transition-opacity flex items-center justify-center"
          :disabled="installing"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          {{ installing ? '설치 중...' : '앱 설치하기' }}
        </button>

        <div v-if="!showInstallButton && !isInstalled" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <p class="text-sm text-yellow-800">
            이 브라우저에서는 앱 설치가 지원되지 않습니다.<br>
            Chrome 또는 Edge 브라우저를 사용해주세요.
          </p>
        </div>

        <div class="mt-6 space-y-2 text-sm text-gray-600">
          <p><strong>설치 방법:</strong></p>
          <ul class="list-disc list-inside space-y-1 text-left">
            <li>Chrome/Edge 브라우저에서 접속</li>
            <li>주소창 오른쪽 "설치" 아이콘 클릭</li>
            <li>또는 메뉴 → "앱 설치" 선택</li>
          </ul>
        </div>
      </div>

      <div v-else class="p-6 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-green-800 font-medium">앱이 이미 설치되어 있습니다!</p>
        <router-link to="/" class="mt-4 inline-block text-green-600 hover:underline">
          앱으로 이동하기 →
        </router-link>
      </div>

      <div class="mt-8">
        <router-link to="/login" class="text-blue-600 hover:underline">
          로그인 페이지로 이동
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const showInstallButton = ref(false)
const installing = ref(false)
const isInstalled = ref(false)
let deferredPrompt = null

onMounted(() => {
  // 이미 설치된 경우 확인
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    isInstalled.value = true
    return
  }

  // PWA 설치 가능 이벤트 캡처
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    showInstallButton.value = true
  })

  // 설치 완료 이벤트
  window.addEventListener('appinstalled', () => {
    console.log('PWA 설치 완료')
    isInstalled.value = true
    showInstallButton.value = false
    deferredPrompt = null
  })
})

async function installApp() {
  if (!deferredPrompt) {
    alert('앱 설치가 불가능합니다. 브라우저 주소창의 설치 아이콘을 사용해주세요.')
    return
  }

  installing.value = true
  try {
    // 설치 팝업 표시
    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice
    
    if (outcome === 'accepted') {
      console.log('사용자가 설치 승인')
      // 설치 완료 이벤트가 발생하므로 여기서는 아무것도 하지 않음
    } else {
      console.log('사용자가 설치 취소')
    }
  } catch (error) {
    console.error('설치 오류:', error)
    alert('설치 중 오류가 발생했습니다.')
  } finally {
    installing.value = false
    deferredPrompt = null
    showInstallButton.value = false
  }
}
</script>
