<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { setToken } from './api'

const router = useRouter()
const route = useRoute()

onMounted(() => {
  // URL 쿼리 파라미터에서 토큰 확인
  const token = route.query.token
  if (token && typeof token === 'string') {
    // 토큰을 localStorage에 저장
    setToken(token)
    
    // 쿼리 파라미터에서 토큰 제거하고 리다이렉트
    router.replace('/')
  }
})
</script>
