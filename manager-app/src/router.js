import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from './api'

const routes = [
  {
    path: '/install',
    name: 'Install',
    component: () => import('./views/Install.vue'),
  },
  {
    path: '/',
    component: () => import('./views/Layout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/requests' },
      { path: 'requests', name: 'Requests', component: () => import('./views/Requests.vue') },
      { path: 'applications', name: 'Applications', component: () => import('./views/Applications.vue') },
      { path: 'schedule', name: 'Schedule', component: () => import('./views/Schedule.vue') },
      { path: 'settings', name: 'Settings', component: () => import('./views/Settings.vue') },
    ],
  },
  // 모든 경로에 대한 fallback (카페24 로그인 페이지로 리다이렉트)
  {
    path: '/:pathMatch(.*)*',
    redirect: () => {
      // 카페24 로그인 페이지로 리다이렉트
      const apiBase = import.meta.env.VITE_API_BASE || ''
      return apiBase ? `${apiBase}/manager/login` : '/manager/login'
    },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = getToken()
  
  // /install 경로는 인증 불필요
  if (to.path === '/install') {
    next()
    return
  }
  
  // 토큰이 없으면 카페24 로그인 페이지로 리다이렉트
  if (!token) {
    const apiBase = import.meta.env.VITE_API_BASE || ''
    const loginUrl = apiBase ? `${apiBase}/manager/login` : '/manager/login'
    // window.location을 사용하여 완전한 페이지 리다이렉트
    window.location.href = loginUrl
    return
  }
  
  next()
})

export default router
