/**
 * API 클라이언트 (매니저 앱)
 * - 베이스 URL: VITE_API_BASE (Vercel 배포 시 웹호스팅 API 주소)
 * - 로컬: 프록시 /api → PHP 서버
 */

const base = import.meta.env.VITE_API_BASE || ''

export function getToken() {
  return localStorage.getItem('manager_token') || ''
}

export async function api(path, options = {}) {
  const url = base + path
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  }
  const token = getToken()
  if (token) headers['Authorization'] = 'Bearer ' + token

  // 디버깅: API 호출 정보 출력
  if (import.meta.env.DEV) {
    console.log('API 호출:', url, options.method || 'GET')
  }

  try {
    const res = await fetch(url, { ...options, headers })
    const data = await res.json().catch(() => ({}))
    
    if (!res.ok) {
      const err = new Error(data.error || '요청에 실패했습니다.')
      err.status = res.status
      err.data = data
      if (import.meta.env.DEV) {
        console.error('API 오류:', res.status, data)
      }
      throw err
    }
    return data
  } catch (e) {
    // 네트워크 오류 처리
    if (e.name === 'TypeError' && e.message.includes('fetch')) {
      const networkErr = new Error('서버에 연결할 수 없습니다. PHP 서버가 실행 중인지 확인해주세요.')
      networkErr.status = 0
      networkErr.data = { error: 'Network error' }
      throw networkErr
    }
    throw e
  }
}

export function setToken(token) {
  if (token) localStorage.setItem('manager_token', token)
  else localStorage.removeItem('manager_token')
}

export function getStoredUser() {
  try {
    const s = localStorage.getItem('manager_user')
    return s ? JSON.parse(s) : null
  } catch {
    return null
  }
}

export function setStoredUser(user) {
  if (user) localStorage.setItem('manager_user', JSON.stringify(user))
  else localStorage.removeItem('manager_user')
}

export async function login(phone, password) {
  const data = await api('/api/manager/login', {
    method: 'POST',
    body: JSON.stringify({ phone, password }),
  })
  if (data.token) setToken(data.token)
  if (data.user) setStoredUser(data.user)
  return data
}

export function logout() {
  setToken('')
  setStoredUser(null)
}

export async function fetchMe() {
  return api('/api/manager/me')
}

export async function fetchRequests() {
  return api('/api/manager/requests')
}

export async function fetchApplications() {
  return api('/api/manager/applications')
}

export async function fetchSchedule() {
  return api('/api/manager/schedule')
}

export async function registerDeviceToken(deviceToken, platform = 'web', appVersion = null) {
  return api('/api/manager/register-token', {
    method: 'POST',
    body: JSON.stringify({ device_token: deviceToken, platform, app_version: appVersion }),
  })
}
