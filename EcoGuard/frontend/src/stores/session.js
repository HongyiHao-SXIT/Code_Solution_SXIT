import { reactive } from 'vue'
import { getJson } from '../lib/api'

const DEFAULT_INITIAL_STATE = {
  authUser: null,
  nextPath: null,
}

function readInitialState() {
  const el = document.getElementById('app-initial-state')
  if (!el) {
    return DEFAULT_INITIAL_STATE
  }

  try {
    return {
      ...DEFAULT_INITIAL_STATE,
      ...(JSON.parse(el.textContent || '{}') || {}),
    }
  } catch {
    return DEFAULT_INITIAL_STATE
  }
}

const initialState = readInitialState()

export const sessionState = reactive({
  user: initialState.authUser || null,
  nextPath: initialState.nextPath || null,
  hydrated: Boolean(initialState.authUser),
})

function applySessionUser(user) {
  sessionState.user = user || null
  sessionState.hydrated = true
  return sessionState.user
}

export async function ensureSession() {
  if (sessionState.hydrated) {
    return sessionState.user
  }

  try {
    const payload = await getJson('/api/web/session')
    return applySessionUser(payload.user)
  } catch {
    return applySessionUser(null)
  }
}

export function setSessionUser(user) {
  return applySessionUser(user)
}

export function clearSessionUser() {
  return applySessionUser(null)
}

/**
 * 派发自定义 flash 事件，由 App 层或 toast 组件监听并展示。
 * 不再向 /api/web/client-log 发送 HTTP 请求。
 */
export function pushFlash(message, category = 'success') {
  const text = String(message || '').trim()
  if (!text) return

  window.dispatchEvent(
    new CustomEvent('app:flash', {
      detail: { message: text, category },
    }),
  )
}