import { computed, ref } from 'vue'

const favoritesKey = 'academic_scholar_favorites'
const readingKey = 'academic_scholar_reading_list'
const currentShelfUserId = ref(null)
let currentApiBaseUrl = ''

function safeParse(key) {
  const raw = localStorage.getItem(key)
  if (!raw) {
    return []
  }

  try {
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : []
  } catch (error) {
    localStorage.removeItem(key)
    return []
  }
}

const favorites = ref(safeParse(favoritesKey))
const readingList = ref(safeParse(readingKey))

function setShelfData(data) {
  favorites.value = Array.isArray(data?.favorites) ? data.favorites : []
  readingList.value = Array.isArray(data?.readingList) ? data.readingList : []
}

function loadLocalShelf() {
  favorites.value = safeParse(favoritesKey)
  readingList.value = safeParse(readingKey)
}

function persist() {
  if (currentShelfUserId.value) {
    return
  }
  localStorage.setItem(favoritesKey, JSON.stringify(favorites.value))
  localStorage.setItem(readingKey, JSON.stringify(readingList.value))
}

function hasPaper(list, id) {
  return list.some((item) => item.id === id)
}

function upsert(list, paper) {
  const index = list.findIndex((item) => item.id === paper.id)
  if (index >= 0) {
    list[index] = { ...list[index], ...paper }
  } else {
    list.unshift(paper)
  }
}

function removeById(list, id) {
  const index = list.findIndex((item) => item.id === id)
  if (index >= 0) {
    list.splice(index, 1)
  }
}

async function requestServer(method, path) {
  const response = await fetch(`${currentApiBaseUrl}${path}`, { method })
  const data = await response.json()
  if (!response.ok) {
    throw new Error(data.message || 'Shelf synchronization failed.')
  }
  return data
}

async function setSession({ apiBaseUrl, userId }) {
  currentApiBaseUrl = apiBaseUrl || currentApiBaseUrl
  currentShelfUserId.value = userId || null

  if (!currentShelfUserId.value) {
    loadLocalShelf()
    return
  }

  const data = await requestServer('GET', `/api/users/${currentShelfUserId.value}/shelf`)
  setShelfData(data)
}

async function toggleFavorite(paper) {
  if (currentShelfUserId.value) {
    const exists = hasPaper(favorites.value, paper.id)
    const method = exists ? 'DELETE' : 'POST'
    const data = await requestServer(method, `/api/users/${currentShelfUserId.value}/shelf/favorites/${paper.id}`)
    setShelfData(data)
    return
  }

  if (hasPaper(favorites.value, paper.id)) {
    removeById(favorites.value, paper.id)
  } else {
    upsert(favorites.value, paper)
  }
  persist()
}

async function toggleReading(paper) {
  if (currentShelfUserId.value) {
    const exists = hasPaper(readingList.value, paper.id)
    const method = exists ? 'DELETE' : 'POST'
    const data = await requestServer(method, `/api/users/${currentShelfUserId.value}/shelf/reading/${paper.id}`)
    setShelfData(data)
    return
  }

  if (hasPaper(readingList.value, paper.id)) {
    removeById(readingList.value, paper.id)
  } else {
    upsert(readingList.value, paper)
  }
  persist()
}

async function removeFavorite(id) {
  if (currentShelfUserId.value) {
    const data = await requestServer('DELETE', `/api/users/${currentShelfUserId.value}/shelf/favorites/${id}`)
    setShelfData(data)
    return
  }
  removeById(favorites.value, id)
  persist()
}

async function removeReading(id) {
  if (currentShelfUserId.value) {
    const data = await requestServer('DELETE', `/api/users/${currentShelfUserId.value}/shelf/reading/${id}`)
    setShelfData(data)
    return
  }
  removeById(readingList.value, id)
  persist()
}

function syncPaper(paper) {
  if (hasPaper(favorites.value, paper.id)) {
    upsert(favorites.value, paper)
  }
  if (hasPaper(readingList.value, paper.id)) {
    upsert(readingList.value, paper)
  }
  persist()
}

export function usePaperShelf() {
  const favoriteIds = computed(() => new Set(favorites.value.map((item) => item.id)))
  const readingIds = computed(() => new Set(readingList.value.map((item) => item.id)))

  return {
    favorites,
    readingList,
    favoriteIds,
    readingIds,
    setSession,
    toggleFavorite,
    toggleReading,
    removeFavorite,
    removeReading,
    syncPaper,
  }
}
