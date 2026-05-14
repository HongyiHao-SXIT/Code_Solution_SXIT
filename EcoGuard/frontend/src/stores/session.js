import { reactive } from 'vue'
import { getJson } from '../lib/api'

function readInitialState() {
  const el = document.getElementById('app-initial-state')
  if (!el) {
    return {
      authUser: null,
      flashMessages: [],
      nextPath: null,
      requestPath: window.location.pathname,
    }
  }

  try {
    return JSON.parse(el.textContent || '{}')
  } catch {
    return {
      authUser: null,
      flashMessages: [],
      nextPath: null,
      requestPath: window.location.pathname,
    }
  }
}

const initialState = readInitialState()

export const sessionState = reactive({
  user: initialState.authUser || null,
  flashes: Array.isArray(initialState.flashMessages) ? [...initialState.flashMessages] : [],
  nextPath: initialState.nextPath || null,
  hydrated: Boolean(initialState.authUser),
})

export async function ensureSession() {
  if (sessionState.hydrated) {
    return sessionState.user
  }

  try {
    const payload = await getJson('/api/web/session')
    sessionState.user = payload.user || null
  } catch {
    sessionState.user = null
  }

  sessionState.hydrated = true
  return sessionState.user
}

export function setSessionUser(user) {
  sessionState.user = user || null
  sessionState.hydrated = true
}

export function clearSessionUser() {
  sessionState.user = null
  sessionState.hydrated = true
}

export function pushFlash(message, category = 'success') {
  sessionState.flashes.unshift({
    id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
    message,
    category,
  })
}

export function removeFlash(id) {
  const index = sessionState.flashes.findIndex((item) => item.id === id)
  if (index >= 0) {
    sessionState.flashes.splice(index, 1)
  }
}
