<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { usePaperShelf } from '../composables/usePaperShelf'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
const route = useRoute()
const userStorageKey = 'academic_scholar_user'

const loading = ref(false)
const errorMessage = ref('')
const paper = ref(null)
const recommendations = ref([])
const recsLoading = ref(false)
const toastMessage = ref('')
let toastTimer = null

const { favoriteIds, readingIds, setSession, toggleFavorite, toggleReading, syncPaper } = usePaperShelf()

const isFavorite = computed(() => paper.value && favoriteIds.value.has(paper.value.id))
const isReading = computed(() => paper.value && readingIds.value.has(paper.value.id))

const yearDisplay = computed(() => {
  if (!paper.value?.publicationDate) return 'n.d.'
  return paper.value.publicationDate.length >= 4 ? paper.value.publicationDate.slice(0, 4) : 'n.d.'
})

async function fetchPaper() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(`${apiBaseUrl}/api/papers/${route.params.id}`)
    if (!response.ok) {
      throw new Error('Paper not found or unavailable.')
    }

    paper.value = await response.json()
    syncPaper(paper.value)

    recsLoading.value = true
    try {
      const recResponse = await fetch(`${apiBaseUrl}/api/papers/${route.params.id}/recommendations?size=6`)
      recommendations.value = recResponse.ok ? await recResponse.json() : []
    } catch {
      recommendations.value = []
    } finally {
      recsLoading.value = false
    }
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to load paper detail.'
    paper.value = null
    recommendations.value = []
  } finally {
    loading.value = false
  }
}

function buildCitation() {
  if (!paper.value) {
    return ''
  }
  const journal = paper.value.journal || 'Unknown Journal'
  const doi = paper.value.doi ? ` https://doi.org/${paper.value.doi}` : ''
  return `${paper.value.authors || 'Unknown author'} (${yearDisplay.value}). ${paper.value.title}. ${journal}.${doi}`.trim()
}

function authorList() {
  if (!paper.value?.authors) return []
  return paper.value.authors.split(';').map((a) => a.trim()).filter(Boolean)
}

async function copyCitation() {
  try {
    await navigator.clipboard.writeText(buildCitation())
    showToast('Citation copied')
  } catch (error) {
    showToast('Copy failed, please copy manually')
  }
}

async function copyShareLink() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    showToast('Share link copied')
  } catch (error) {
    showToast('Cannot copy share link')
  }
}

async function toggleFavoriteState() {
  if (!paper.value) {
    return
  }
  const wasFavorite = isFavorite.value
  try {
    await toggleFavorite(paper.value)
    showToast(wasFavorite ? 'Removed from favorites' : 'Added to favorites')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update favorites')
  }
}

async function toggleReadingState() {
  if (!paper.value) {
    return
  }
  const wasReading = isReading.value
  try {
    await toggleReading(paper.value)
    showToast(wasReading ? 'Removed from reading list' : 'Added to reading list')
  } catch (error) {
    showToast(error instanceof Error ? error.message : 'Unable to update reading list')
  }
}

function showToast(message) {
  toastMessage.value = message
  if (toastTimer) {
    clearTimeout(toastTimer)
  }
  toastTimer = setTimeout(() => {
    toastMessage.value = ''
  }, 2000)
}

onMounted(() => {
  const raw = localStorage.getItem(userStorageKey)
  let userId = null
  if (raw) {
    try {
      userId = JSON.parse(raw)?.id || null
    } catch (error) {
      userId = null
    }
  }

  setSession({ apiBaseUrl, userId }).catch(() => {
    showToast('Shelf sync failed, using local data')
  })

  fetchPaper()
})

watch(() => route.params.id, fetchPaper)
</script>

<template>
  <div class="detail-page">
    <header class="detail-topbar">
      <RouterLink class="back-link" to="/">← Back to search</RouterLink>
      <button type="button" class="share-link-button" @click="copyShareLink">Copy share link</button>
    </header>

    <main class="detail-main">
      <!-- Skeleton loading -->
      <div v-if="loading" class="detail-skeleton">
        <div class="sk-line short"></div>
        <div class="sk-line title"></div>
        <div class="sk-line medium"></div>
        <div class="sk-line wide"></div>
        <div class="sk-line"></div>
        <div class="sk-line"></div>
        <div class="sk-line wide"></div>
      </div>

      <p v-else-if="errorMessage" class="detail-status error">{{ errorMessage }}</p>

      <article v-else-if="paper" class="detail-card">
        <p class="detail-url">{{ paper.url || 'https://scholar.local/paper/' + paper.id }}</p>
        <h1>{{ paper.title }}</h1>

        <p class="detail-authors">
          <span v-for="(author, idx) in authorList()" :key="idx">
            <span v-if="idx > 0">; </span>
            <span class="author-name">{{ author }}</span>
          </span>
        </p>
        <p class="detail-meta">{{ paper.journal || 'Unknown Journal' }} · {{ paper.publicationDate || 'Unknown date' }}</p>

        <section class="detail-section">
          <h2>Abstract</h2>
          <p>{{ paper.abstractText }}</p>
        </section>

        <section class="detail-section">
          <h2>Identifiers</h2>
          <p><strong>DOI:</strong> {{ paper.doi || 'Not available' }}</p>
          <p><strong>Paper ID:</strong> {{ paper.id }}</p>
        </section>

        <div class="detail-actions">
          <button type="button" @click="toggleFavoriteState">
            {{ isFavorite ? '★ Favorited' : '☆ Add to favorites' }}
          </button>
          <button type="button" @click="toggleReadingState">
            {{ isReading ? '📖 In reading list' : '📘 Add to reading list' }}
          </button>
          <button type="button" class="action-secondary" @click="copyCitation">Copy citation</button>
          <a v-if="paper.url" :href="paper.url" target="_blank" rel="noreferrer" class="action-link">Open source →</a>
        </div>

        <section class="recommendation-section">
          <h2>Recommended papers</h2>
          <div v-if="recsLoading" class="rec-skeleton">
            <div class="sk-line medium"></div>
            <div class="sk-line"></div>
            <div class="sk-line medium"></div>
            <div class="sk-line"></div>
          </div>
          <ul v-else-if="recommendations.length" class="recommendation-list">
            <li v-for="item in recommendations" :key="item.id">
              <RouterLink :to="`/paper/${item.id}`">{{ item.title }}</RouterLink>
              <p>{{ item.authors }} · {{ item.journal }}</p>
            </li>
          </ul>
          <p v-else class="detail-status">No recommendations available for this paper yet.</p>
        </section>
      </article>
    </main>

    <transition name="toast-fade">
      <div v-if="toastMessage" class="toast">{{ toastMessage }}</div>
    </transition>
  </div>
</template>

<style scoped>
.detail-page {
  width: min(1000px, calc(100vw - 28px));
  margin: 18px auto 36px;
}

.detail-topbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.back-link,
.share-link-button {
  border: 0;
  border-radius: 999px;
  padding: 10px 14px;
  text-decoration: none;
  color: #1a73e8;
  background: #edf4ff;
  font-weight: 600;
}

.share-link-button {
  cursor: pointer;
}

.detail-main {
  border: 1px solid var(--scholar-border);
  border-radius: 28px;
  background: var(--scholar-card);
  box-shadow: var(--scholar-shadow);
  padding: 28px;
}

.detail-status {
  color: #567396;
}

.detail-status.error {
  color: #b42318;
}

/* Skeleton */
.detail-skeleton,
.rec-skeleton {
  display: grid;
  gap: 12px;
}

.sk-line {
  height: 16px;
  border-radius: 8px;
  background: linear-gradient(90deg, rgba(200, 215, 235, 0.45), rgba(220, 230, 245, 0.7), rgba(200, 215, 235, 0.45));
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

.sk-line.title {
  height: 32px;
  width: 65%;
}

.sk-line.medium {
  width: 80%;
}

.sk-line.wide {
  width: 95%;
}

.sk-line.short {
  width: 30%;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.detail-url {
  color: #188038;
  margin-bottom: 8px;
  word-break: break-all;
}

h1 {
  font-family: var(--font-serif);
  color: #1a0dab;
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1.08;
  margin-bottom: 10px;
}

.detail-authors {
  color: #284d78;
  line-height: 1.6;
}

.author-name {
  white-space: nowrap;
}

.detail-meta {
  color: #617791;
  margin-bottom: 16px;
}

.detail-section {
  margin-top: 18px;
}

.detail-section h2 {
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #315882;
  margin-bottom: 8px;
}

.detail-section p {
  color: #213b5c;
  line-height: 1.75;
}

.detail-actions {
  margin-top: 24px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.detail-actions button,
.detail-actions a {
  border: 0;
  border-radius: 999px;
  padding: 10px 14px;
  text-decoration: none;
  cursor: pointer;
  background: #edf4ff;
  color: #1a73e8;
  font-weight: 600;
}

.detail-actions button:first-child,
.detail-actions button:nth-child(2) {
  background: linear-gradient(135deg, #1a73e8, #0f9d58);
  color: #fff;
}

.action-secondary {
  background: #edf4ff !important;
  color: #1a73e8 !important;
}

.action-link {
  background: transparent !important;
  padding: 0;
  color: #1a73e8 !important;
}

.recommendation-section {
  margin-top: 26px;
}

.recommendation-list {
  margin-top: 12px;
  list-style: none;
  display: grid;
  gap: 12px;
}

.recommendation-list li {
  border: 1px solid rgba(125, 150, 185, 0.22);
  border-radius: 14px;
  padding: 12px 14px;
  background: #f8fbff;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.recommendation-list li:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(25, 53, 89, 0.06);
}

.recommendation-list a {
  color: #1a0dab;
  font-weight: 600;
}

.recommendation-list p {
  margin-top: 4px;
  color: #5f748f;
  font-size: 13px;
}

.toast {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(21, 38, 63, 0.96);
  color: #fff;
  padding: 10px 14px;
  border-radius: 999px;
  font-size: 14px;
  z-index: 40;
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(8px);
}

@media (max-width: 760px) {
  .detail-main {
    padding: 20px;
    border-radius: 20px;
  }

  .detail-topbar {
    flex-direction: column;
  }
}
</style>