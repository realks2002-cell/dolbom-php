<template>
  <div>
    <h1 class="text-xl font-bold">새 요청</h1>
    <p class="mt-1 text-gray-600">활동 지역 내 새 요청 목록입니다.</p>
    <div class="mt-6 rounded-lg border bg-white p-6">
      <p v-if="loading">불러오는 중…</p>
      <p v-else-if="items.length === 0" class="text-gray-500">{{ message || '새 요청이 없습니다.' }}</p>
      <ul v-else class="space-y-4">
        <li v-for="(item, i) in items" :key="i" class="rounded-lg border p-4">
          {{ item }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchRequests } from '@/api'

const loading = ref(true)
const items = ref([])
const message = ref('')

onMounted(async () => {
  try {
    const data = await fetchRequests()
    items.value = data.items || []
    message.value = data.message || ''
  } catch {
    message.value = '목록을 불러올 수 없습니다.'
  } finally {
    loading.value = false
  }
})
</script>
