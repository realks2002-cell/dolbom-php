<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 font-sans">
    <h1 class="text-2xl font-bold">Hangbok77 매니저</h1>
    <p class="mt-2 text-gray-600">매니저 계정으로 로그인하세요.</p>

    <form class="mt-8 w-full max-w-md space-y-4" @submit.prevent="submit">
      <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {{ error }}
      </div>
      <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">전화번호</label>
        <input
          id="phone"
          v-model="phone"
          type="tel"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3"
          placeholder="01012345678"
          pattern="[0-9]*"
          inputmode="numeric"
          required
          autocomplete="tel"
          @input="phone = phone.replace(/[^0-9]/g, '')"
        />
        <p class="mt-1 text-xs text-gray-500">숫자만 입력해주세요</p>
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">비밀번호</label>
        <div class="relative">
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 pr-12"
            placeholder="비밀번호 입력"
            required
            autocomplete="current-password"
          />
          <button
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700"
            @click="showPassword = !showPassword"
            aria-label="비밀번호 표시/숨기기"
          >
            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
            </svg>
          </button>
        </div>
      </div>
      <button
        type="submit"
        class="flex min-h-[44px] w-full items-center justify-center rounded-lg bg-blue-600 font-medium text-white hover:opacity-90 disabled:opacity-50"
        :disabled="loading"
      >
        {{ loading ? '로그인 중…' : '로그인' }}
      </button>
    </form>

    <div class="mt-6 text-center space-y-2">
      <p class="text-sm text-gray-600">
        계정이 없으신가요?
        <a :href="signupUrl" class="text-blue-600 hover:underline font-medium">회원가입</a>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { login } from '@/api'

const router = useRouter()
const phone = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

const signupUrl = computed(() => {
  const apiBase = import.meta.env.VITE_API_BASE || ''
  return apiBase ? `${apiBase}/manager/signup` : '/manager/signup'
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await login(phone.value, password.value)
    router.replace('/')
  } catch (e) {
    console.error('로그인 오류:', e)
    // 더 자세한 에러 메시지 표시
    if (e.data?.error) {
      error.value = e.data.error
    } else if (e.message) {
      error.value = e.message
    } else if (e.status === 0 || e.status === undefined) {
      error.value = '서버에 연결할 수 없습니다. PHP 서버가 실행 중인지 확인해주세요.'
    } else {
      error.value = `로그인에 실패했습니다. (상태 코드: ${e.status || '알 수 없음'})`
    }
  } finally {
    loading.value = false
  }
}
</script>
