import { ref, watch, type Ref } from 'vue'

export function useStorage<T>(key: string, defaultValue: T): Ref<T> {
  let initial = defaultValue
  try {
    const stored = localStorage.getItem(key)
    if (stored) {
      initial = JSON.parse(stored) as T
    }
  } catch {
    // corrupt data, use default
  }

  const data = ref<T>(initial) as Ref<T>

  watch(data, (newVal) => {
    try {
      localStorage.setItem(key, JSON.stringify(newVal))
    } catch {
      // Storage full or unavailable
    }
  }, { deep: true })

  return data
}