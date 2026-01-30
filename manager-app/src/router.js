import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from './api'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('./views/Login.vue'),
    meta: { guest: true },
  },
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
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = getToken()
  if (to.meta.requiresAuth && !token) return next('/login')
  if (to.meta.guest && token) return next('/')
  next()
})

export default router
